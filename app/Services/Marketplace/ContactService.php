<?php

namespace App\Services\Marketplace;

use App\Models\Message;
use App\Models\Notification;
use App\Models\RecruiterContact;
use App\Models\RecruiterSavedProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function contact(User $recruiter, array $data): array
    {
        $recruiterProfile = $recruiter->recruiterProfile;

        if ($recruiterProfile->credits_remaining <= 0) {
            throw new \Exception('Crédits insuffisants.', 402);
        }

        $alreadyContacted = RecruiterContact::where('recruiter_id', $recruiter->id)
            ->where('developer_id', $data['developer_id'])
            ->exists();

        if ($alreadyContacted) {
            throw new \Exception('Vous avez déjà contacté ce développeur.', 409);
        }

        $contact = DB::transaction(function () use ($recruiter, $recruiterProfile, $data) {
            $contact = RecruiterContact::create([
                'recruiter_id'   => $recruiter->id,
                'developer_id'   => $data['developer_id'],
                'job_posting_id' => $data['job_posting_id'] ?? null,
                'status'         => 'sent',
                'message'        => $data['message'],
                'credits_spent'  => 1,
            ]);

            Message::create([
                'sender_id'   => $recruiter->id,
                'receiver_id' => $data['developer_id'],
                'contact_id'  => $contact->id,
                'body'        => $data['message'],
            ]);

            $recruiterProfile->decrement('credits_remaining');
            $recruiterProfile->increment('total_contacts');

            Notification::create([
                'user_id' => $data['developer_id'],
                'type'    => 'recruiter_contact',
                'title'   => "Nouveau message de {$recruiterProfile->company_name}",
                'body'    => "Un recruteur vous a contacté.",
                'data'    => [
                    'contact_id'   => $contact->id,
                    'recruiter_id' => $recruiter->id,
                    'company_name' => $recruiterProfile->company_name,
                ],
            ]);

            return $contact;
        });

        return [
            'contact'           => $contact,
            'credits_remaining' => $recruiterProfile->fresh()->credits_remaining,
        ];
    }

    public function getContacts(User $recruiter): LengthAwarePaginator
    {
        return RecruiterContact::where('recruiter_id', $recruiter->id)
            ->with(['developer:id,name,username,avatar_url', 'jobPosting:id,title'])
            ->latest()
            ->paginate(20);
    }

    public function getSaved(User $recruiter): LengthAwarePaginator
    {
        return RecruiterSavedProfile::where('recruiter_id', $recruiter->id)
            ->with([
                'developer:id,name,username,avatar_url,is_available,xp_total,level',
                'developer.developerProfile:user_id,headline,current_level,overall_score,location_country',
                'developer.badges' => fn($q) => $q->where('badge_type', 'certification')->limit(3),
            ])
            ->latest('saved_at')
            ->paginate(20);
    }

    public function saveProfile(User $recruiter, string $developerId, ?string $note): void
    {
        RecruiterSavedProfile::updateOrCreate(
            ['recruiter_id' => $recruiter->id, 'developer_id' => $developerId],
            ['note' => $note]
        );
    }

    public function unsaveProfile(User $recruiter, string $developerId): void
    {
        RecruiterSavedProfile::where('recruiter_id', $recruiter->id)
            ->where('developer_id', $developerId)
            ->delete();
    }
}
