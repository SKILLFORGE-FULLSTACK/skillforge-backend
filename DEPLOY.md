# Déploiement gratuit — Oracle Cloud (backend) + Vercel (frontend)

## Vue d'ensemble

- **Backend** (Laravel + Postgres + Redis + RabbitMQ + MinIO + Judge0 + LibreTranslate) : une VM Oracle Cloud "Always Free" (gratuite à vie, pas un essai), via `docker-compose.prod.yml`.
- **Frontend** (Next.js) : Vercel, gratuit pour un usage perso/hobby.
- **Coût réel** : 0€. Seul un nom de domaine est optionnel (~10€/an) — sans domaine, on peut utiliser un sous-domaine gratuit type `sslip.io` pointant sur l'IP de la VM.

---

## Étape 0 — Mettre le code sur GitHub (recommandé)

Ce dépôt n'est pas encore versionné avec git. Le plus simple pour déployer (Vercel + mises à jour sur la VM) est de le pousser sur GitHub :

```bash
# à la racine de chaque projet (skillforge-backend, skillforge-frontend)
git init
git add .
git commit -m "Initial commit"
# créer un dépôt vide sur github.com, puis :
git remote add origin https://github.com/<toi>/skillforge-backend.git
git push -u origin main
```

*(Dis-moi si tu veux que je fasse le `git init` + premier commit maintenant — je ne peux pas créer le dépôt GitHub à ta place, ça se fait en 30 secondes sur github.com/new.)*

---

## Étape 1 — Créer la VM Oracle Cloud (manuel, ~10 min)

1. Va sur [cloud.oracle.com](https://cloud.oracle.com) → **Start for free**. Carte bancaire demandée pour vérification d'identité, **jamais débitée** tant que tu restes sur les ressources "Always Free".
2. Une fois le compte créé, menu ☰ → **Compute** → **Instances** → **Create Instance**.
3. **Image** : Ubuntu 22.04 (ou 24.04).
4. **Shape** : clique "Change shape" → **Ampere (ARM)** → `VM.Standard.A1.Flex` → mets **4 OCPU / 24 Go RAM** (le max gratuit). C'est le point important : ne pas prendre le shape AMD par défaut (beaucoup plus limité).
5. **Clé SSH** : laisse Oracle en générer une paire, télécharge la clé privée (tu en auras besoin pour te connecter).
6. Crée l'instance, note son **IP publique**.
7. **Ouvrir les ports** : menu ☰ → **Networking** → **Virtual Cloud Networks** → ton VCN → **Security Lists** → règle par défaut → **Add Ingress Rules** : ajoute les ports **80** et **443** (TCP, source `0.0.0.0/0`). Le port 22 (SSH) est déjà ouvert par défaut.

## Étape 2 — Préparer la VM

```bash
ssh -i ta_cle.key ubuntu@<IP_PUBLIQUE>

# Installer Docker
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
newgrp docker

# Vérifier
docker run hello-world
```

## Étape 3 — Récupérer le code et configurer

```bash
git clone https://github.com/<toi>/skillforge-backend.git
cd skillforge-backend

cp .env.production.example .env
nano .env   # remplir DB_PASSWORD, RABBITMQ_PASSWORD, AWS_SECRET_ACCESS_KEY,
            # API_DOMAIN, FRONTEND_URL, GROQ_API_KEY, GOOGLE_/GITHUB_ credentials...

# Générer une vraie clé d'application (ne jamais réutiliser celle de dev)
docker run --rm -v $(pwd):/app -w /app composer:2 composer install --no-dev
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
# → coller le résultat dans APP_KEY= du .env
```

## Étape 4 — Lancer la stack

```bash
docker compose -f docker-compose.prod.yml --env-file .env up -d --build

# Attendre que postgres soit healthy, puis lancer les migrations
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Créer le compte admin (voir app/Console/Commands/ManageAdminCommand.php)
docker compose -f docker-compose.prod.yml exec app php artisan admin:create toi@tondomaine.com
```

Caddy obtient automatiquement un certificat HTTPS Let's Encrypt pour `API_DOMAIN` dès que ce domaine pointe vers l'IP de la VM (enregistrement DNS `A`). Sans domaine : utilise `<IP>.sslip.io` (résout automatiquement vers cette IP, gratuit, aucune config DNS à faire) comme valeur d'`API_DOMAIN`.

## Étape 5 — Frontend sur Vercel

1. [vercel.com](https://vercel.com) → **Add New Project** → importe le dépôt `skillforge-frontend` depuis GitHub.
2. Root directory : `skillforge-frontend` (si mono-repo) ou racine directe.
3. Variable d'environnement à ajouter : `NEXT_PUBLIC_API_URL=https://api.tondomaine.com/api`
4. Deploy. Vercel republie automatiquement à chaque `git push`.

## Étape 6 — Vérifications post-déploiement

- `curl https://api.tondomaine.com/up` → doit répondre 200 (health check Laravel).
- Se connecter depuis le frontend Vercel, vérifier qu'un entretien démarre (Judge0), qu'une traduction se déclenche (LibreTranslate).
- `docker compose -f docker-compose.prod.yml logs -f queue-worker` → doit tourner sans redémarrage en boucle (contrairement au setup dev, `restart: unless-stopped` le relance automatiquement s'il plante).
- Mettre à jour les URIs de redirection OAuth autorisées côté Google Cloud Console / GitHub OAuth App avec les vraies valeurs `https://api.tondomaine.com/api/auth/.../callback`.

## Points d'attention connus

- **Fichiers privés** (CV, documents sensibles) : le passthrough `/storage/*` dans le `Caddyfile` rend tout le bucket MinIO public en lecture. Si certains fichiers doivent rester privés, il faudra passer par `Storage::disk('s3')->temporaryUrl(...)` (URLs signées à durée limitée) plutôt que ce passthrough — non fait ici, à voir selon besoin réel.
- **Kafka / Zookeeper / Elasticsearch** : présents dans `docker-compose.yml` (dev) mais non utilisés par le code applicatif — volontairement absents de `docker-compose.prod.yml` pour économiser des ressources.
- **Mailpit** : outil de dev uniquement (intercepte les emails sans les envoyer) — remplacé en prod par un vrai fournisseur SMTP à renseigner dans `.env` (ex: Brevo ou Resend, gratuits jusqu'à ~300 emails/jour).
- **Mises à jour** : `git pull && docker compose -f docker-compose.prod.yml up -d --build` puis `docker compose -f docker-compose.prod.yml exec app php artisan migrate --force` si nouvelles migrations.
