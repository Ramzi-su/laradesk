# LaraDesk — Plan complet du projet

> Document de référence pour Claude Code. À placer à la racine du projet.
> Objectif : un projet portfolio Laravel professionnel, pour une candidature de Développeur Full Stack Laravel.

---

## 1. Contexte et objectif

LaraDesk est une plateforme web de gestion de tickets de support client.
Des clients ouvrent des tickets, des agents les traitent, un administrateur supervise l'ensemble.

Le projet doit démontrer une maîtrise solide de l'écosystème Laravel : Eloquent, Blade, Artisan,
Policies, Form Requests, Queues, Notifications, Scheduler, Sanctum, API Resources, tests PHPUnit.

**Priorités, dans l'ordre :** code propre et idiomatique Laravel > fonctionnalités complètes > design soigné.

---

## 2. Stack technique

- **Backend :** dernière version stable de Laravel, PHP 8.2+
- **Base de données :** MySQL (SQLite en mémoire pour les tests)
- **Frontend :** Blade + composants Blade, Tailwind CSS (via Vite), Alpine.js si besoin, Chart.js
- **Authentification web :** starter kit officiel Blade (Laravel Breeze).
  Si Breeze n'est pas compatible avec la version installée, utiliser Laravel Fortify avec des vues Blade.
  Vérifier la compatibilité avant d'installer.
- **API :** Laravel Sanctum (tokens), API Resources
- **Files d'attente :** driver `database`
- **Mail en local :** driver `log`
- **Tests :** PHPUnit (tests Feature principalement)
- **Conteneurisation :** Laravel Sail (Docker)
- **CI :** GitHub Actions

---

## 3. Conventions

- **Code** (classes, variables, tables, colonnes) : en anglais.
- **Interface utilisateur** (libellés, messages, e-mails) : en français, via les fichiers de traduction `lang/fr`.
- Utiliser des **Enums PHP** pour les statuts, priorités et rôles.
- Contrôleurs fins : logique de validation dans les **Form Requests**, logique d'autorisation dans les **Policies**.
- Toujours utiliser l'**eager loading** pour éviter les problèmes N+1.
- Pas de secrets dans le dépôt : `.env` ignoré, `.env.example` complet et propre.
- Un **commit Git à la fin de chaque étape**, avec un message clair (ex. `feat: ticket management with policies`).
- Après chaque étape : lancer les tests existants et vérifier qu'ils passent.

---

## 4. Modèle de données

### Enums
- `UserRole` : `client`, `agent`, `admin`
- `TicketStatus` : `open`, `in_progress`, `resolved`, `closed`
- `TicketPriority` : `low`, `medium`, `high`, `urgent`

### Tables

**users**
- id, name, email (unique), password, role (enum, défaut `client`), email_verified_at, remember_token, timestamps

**categories**
- id, name, slug (unique), description (nullable), timestamps

**tickets**
- id, reference (unique, format `LD-000123`), title, description (text)
- status (enum, défaut `open`), priority (enum, défaut `medium`)
- category_id (FK categories)
- client_id (FK users) — auteur du ticket
- agent_id (FK users, nullable) — agent assigné
- resolved_at (nullable), closed_at (nullable)
- timestamps, softDeletes
- Index sur status, priority, agent_id, client_id

**comments**
- id, ticket_id (FK, cascade), user_id (FK), body (text), is_internal (bool, défaut false), timestamps
- Un commentaire interne n'est visible que par les agents et l'admin.

**attachments**
- id, ticket_id (FK, cascade), user_id (FK), original_name, path, mime_type, size, timestamps

### Relations Eloquent
- User : hasMany tickets (client), hasMany assignedTickets (agent), hasMany comments
- Ticket : belongsTo client, belongsTo agent, belongsTo category, hasMany comments, hasMany attachments
- Category : hasMany tickets

### Scopes sur Ticket
- `open()`, `status($status)`, `priority($priority)`, `assignedTo($user)`, `forUser($user)` (selon le rôle), `search($term)` (titre, référence, description)

### Casts et accesseurs
- Casts des enums, dates `resolved_at` / `closed_at`
- Génération automatique de la `reference` à la création (événement `creating` ou observer)

---

## 5. Rôles et autorisations (TicketPolicy)

| Action | Client | Agent | Admin |
|---|---|---|---|
| Voir la liste | ses tickets | tickets assignés + non assignés | tous |
| Voir un ticket | le sien | assigné ou non assigné | tous |
| Créer un ticket | oui | non | oui |
| Modifier titre / description | le sien, si statut `open` | non | oui |
| Changer le statut | fermer le sien s'il est `resolved` | tickets assignés | oui |
| Changer la priorité | non | tickets assignés | oui |
| Assigner un agent | non | s'auto-assigner un ticket non assigné | oui |
| Commenter | le sien | assigné | oui |
| Commentaire interne | non | oui | oui |
| Supprimer (soft delete) | non | non | oui |
| Tableau de bord admin | non | non | oui |
| Gérer les catégories et utilisateurs | non | non | oui |

Middleware ou Gate `admin` pour les routes d'administration.

---

## 6. Fonctionnalités web

### Tickets
- Liste paginée (15 par page) avec recherche et filtres : statut, priorité, catégorie, agent (conservés dans l'URL)
- Création (Form Request) : titre, description, catégorie, priorité, pièces jointes multiples
- Page détail : infos du ticket, badges colorés (statut, priorité), historique des commentaires, pièces jointes téléchargeables
- Actions selon la Policy : changer le statut, la priorité, assigner un agent, commenter, commentaire interne
- Pièces jointes : types autorisés `pdf, png, jpg, jpeg, txt, docx`, 5 Mo max par fichier, stockage sur le disque `local` (privé), téléchargement via une route protégée par la Policy

### Administration
- CRUD des catégories
- Liste des utilisateurs avec changement de rôle
- Tableau de bord (voir section 9)

### Interface
- Layout responsive avec Tailwind, navigation adaptée au rôle
- Composants Blade réutilisables : badge de statut, badge de priorité, carte de statistique, alerte flash
- Messages flash de succès et d'erreur

---

## 7. API REST (Sanctum)

Préfixe `/api/v1`, réponses JSON via API Resources.

| Méthode | Route | Description |
|---|---|---|
| POST | /auth/login | Renvoie un token Sanctum |
| POST | /auth/logout | Révoque le token courant |
| GET | /tickets | Liste paginée, filtres `status`, `priority`, `category`, `search` |
| POST | /tickets | Création |
| GET | /tickets/{ticket} | Détail avec commentaires |
| PATCH | /tickets/{ticket}/status | Changement de statut |
| POST | /tickets/{ticket}/comments | Ajout d'un commentaire |
| GET | /categories | Liste des catégories |

- Mêmes Policies que le web.
- Erreurs JSON cohérentes : 401, 403, 404, 422.
- Limitation de débit (rate limiting) sur les routes API.

---

## 8. Notifications, files d'attente et planification

- `TicketStatusChanged` : e-mail au client quand le statut de son ticket change
- `TicketAssigned` : e-mail à l'agent quand un ticket lui est assigné
- `NewCommentAdded` : e-mail au client ou à l'agent concerné (jamais pour un commentaire interne vers le client)
- Toutes les notifications implémentent `ShouldQueue` (driver `database`)
- Commande Artisan `tickets:close-stale {--days=7}` : ferme les tickets `resolved` sans activité depuis N jours, affiche le nombre de tickets fermés
- Planification quotidienne de cette commande dans le Scheduler

---

## 9. Tableau de bord administrateur

- Cartes : total des tickets, ouverts, en cours, résolus, fermés
- Répartition par priorité
- Tickets ouverts par agent
- Temps moyen de résolution (en heures)
- Graphique Chart.js : tickets créés par jour sur les 30 derniers jours
- Requêtes agrégées optimisées (pas de chargement de tous les tickets en mémoire)

---

## 10. Données de démo (seeders)

- 1 admin : `admin@laradesk.test` / `password`
- 3 agents : `agent1@laradesk.test` à `agent3@laradesk.test` / `password`
- 10 clients dont `client@laradesk.test` / `password`
- 5 catégories réalistes : Facturation, Technique, Compte, Livraison, Autre
- Environ 60 tickets répartis sur les 30 derniers jours, statuts et priorités variés, avec commentaires
- Factories pour tous les modèles

---

## 11. Tests (PHPUnit, Feature)

- Un client ne peut pas voir ni modifier le ticket d'un autre client (403)
- Un client peut créer un ticket ; la validation rejette les données invalides (422 / erreurs de session)
- Un agent peut changer le statut d'un ticket assigné, pas d'un ticket assigné à un autre agent
- Seul l'admin accède au tableau de bord et à la gestion des catégories
- Un commentaire interne n'est pas visible par le client
- Upload : un type de fichier interdit est rejeté
- API : login renvoie un token ; accès sans token = 401 ; filtres de liste fonctionnels
- `tickets:close-stale` ferme uniquement les tickets éligibles
- Notifications envoyées au bon destinataire (`Notification::fake()`)

Tous les tests doivent passer avant chaque commit.

---

## 12. Docker et intégration continue

- Laravel Sail configuré (services : MySQL ; Mailpit optionnel)
- Workflow GitHub Actions `.github/workflows/tests.yml` :
  installation PHP + Composer, copie de `.env.example`, génération de clé, tests PHPUnit sur SQLite,
  déclenché à chaque push et pull request
- Badge de statut CI dans le README

---

## 13. README (en français)

- Présentation du projet et captures d'écran (dossier `docs/`)
- Fonctionnalités
- Stack technique
- Schéma de la base de données (Mermaid)
- Installation locale et avec Sail
- Comptes de démo
- Lancement du worker (`php artisan queue:work`) et du scheduler
- Exemples de requêtes API (curl)
- Lancement des tests
- Auteur : Ramzi Moulahi (GitHub, LinkedIn, Portfolio)

---

## 14. Étapes de réalisation

Chaque étape se termine par : tests verts + commit + court résumé de ce qui a été fait.
**Points de validation** : s'arrêter et attendre la validation du développeur après les étapes 0, 1 et 2.

| Étape | Contenu | Validation |
|---|---|---|
| 0 | Lire ce plan, créer `CLAUDE.md`, proposer le schéma final et poser les questions éventuelles | Oui |
| 1 | Installation Laravel, authentification, migrations, enums, modèles, relations, scopes, factories, seeders | Oui |
| 2 | Policies, contrôleurs web, Form Requests, vues Blade, pièces jointes, commentaires, administration | Oui |
| 3 | API REST Sanctum, API Resources, gestion des erreurs, rate limiting | Non |
| 4 | Notifications en file d'attente, commande Artisan, Scheduler | Non |
| 5 | Tableau de bord administrateur et Chart.js | Non |
| 6 | Tests PHPUnit complets, Sail, GitHub Actions | Non |
| 7 | README, vérification finale (sécurité, `.env`, qualité), préparation du push GitHub | Oui |

---

## 15. Définition de « terminé »

- Toutes les fonctionnalités des sections 5 à 9 fonctionnent dans le navigateur et via l'API
- `php artisan migrate:fresh --seed` fonctionne sans erreur
- Tous les tests passent, en local et dans GitHub Actions
- Aucun secret dans le dépôt
- README complet avec captures d'écran
- Le développeur est capable d'expliquer chaque partie du code
