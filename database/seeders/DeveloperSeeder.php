<?php

namespace Database\Seeders;

use App\Models\DeveloperProfile;
use App\Models\RecruiterProfile;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Database\Seeder;

class DeveloperSeeder extends Seeder
{
    public function run(): void
    {
        // Créer 10 développeurs de test
        $skills = ['Laravel', 'React', 'Next.js', 'PostgreSQL', 'Docker', 'Redis', 'TypeScript', 'Python'];
        $profileLevels = ['junior', 'mid', 'senior'];
        $skillLevels   = ['beginner', 'intermediate', 'advanced', 'expert'];
        $countries = ['Madagascar', 'France', 'Sénégal', 'Côte d\'Ivoire', 'Canada'];

        for ($i = 1; $i <= 10; $i++) {
            $user = User::updateOrCreate(
                ['email' => "dev{$i}@skillforge.io"],  // condition
                [                                        // valeurs
                    'name'           => "Développeur Test {$i}",
                    'username'       => "dev-test-{$i}",
                    'password'       => 'password123',
                    'role'           => 'developer',
                    'xp_total'       => rand(100, 5000),
                    'level'          => rand(1, 8),
                    'current_streak' => rand(0, 30),
                    'is_public'      => true,
                    'is_available'   => rand(0, 1),
                ]
            );

            DeveloperProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'headline'             => "Développeur Fullstack Laravel + React",
                    'bio'                  => "Développeur passionné avec {$i} ans d'expérience.",
                    'years_experience'     => $i,
                    'current_level'        => $profileLevels[array_rand($profileLevels)],
                    'work_mode'            => 'remote',
                    'location_country'     => $countries[array_rand($countries)],
                    'overall_score'        => rand(300, 950),
                    'interview_score'      => rand(50, 95),
                    'cert_score'           => rand(60, 100),
                    'certifications_count' => rand(0, 5),
                ]
            );

            // Supprimer les skills existants avant de recréer
            UserSkill::where('user_id', $user->id)->delete();

            $randomSkills = array_rand(array_flip($skills), rand(3, 6));
            foreach ((array) $randomSkills as $skill) {
                UserSkill::create([
                    'user_id'      => $user->id,
                    'skill_name'   => $skill,
                    'level'        => $skillLevels[array_rand($skillLevels)],
                    'is_certified' => rand(0, 1),
                    'score'        => rand(50, 100),
                ]);
            }
        }

        // Recruteurs
        for ($i = 1; $i <= 2; $i++) {
            $recruiter = User::updateOrCreate(
                ['email' => "recruiter{$i}@skillforge.io"],
                [
                    'name'     => "Recruteur Test {$i}",
                    'username' => "recruiter-{$i}",
                    'password' => 'password123',
                    'role'     => 'recruiter',
                ]
            );

            RecruiterProfile::updateOrCreate(
                ['user_id' => $recruiter->id],
                [
                    'company_name'      => "Tech Company {$i}",
                    'company_size'      => '11-50',
                    'industry'          => 'Technology',
                    'credits_remaining' => 20,
                ]
            );
        }

        $this->command->info('✅ 10 développeurs et 2 recruteurs créés.');
    }
}
