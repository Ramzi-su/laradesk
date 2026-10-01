<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TicketSeeder extends Seeder
{
    private const TICKET_COUNT = 60;

    /**
     * Realistic titles per category slug, for nicer demo screens.
     *
     * @var array<string, array<int, string>>
     */
    private const TITLES = [
        'facturation' => [
            'Double prélèvement sur ma carte bancaire',
            'Facture introuvable dans mon espace client',
            'Demande de remboursement suite à une annulation',
            'Erreur de TVA sur la facture de mars',
            'Changement de moyen de paiement',
        ],
        'technique' => [
            "Erreur 500 lors de l'export PDF",
            "L'application se ferme au démarrage",
            'Les notifications ne s’affichent plus',
            'Impossible de téléverser une image',
            'Lenteurs importantes sur le tableau de bord',
        ],
        'compte' => [
            'Impossible de réinitialiser mon mot de passe',
            "Je ne reçois pas l'e-mail de confirmation",
            'Modifier l’adresse e-mail de mon compte',
            'Compte bloqué après plusieurs tentatives',
            'Suppression de mes données personnelles',
        ],
        'livraison' => [
            'Colis marqué livré mais non reçu',
            'Retard de livraison de ma commande',
            'Article reçu endommagé',
            "Changer l'adresse de livraison",
            'Numéro de suivi invalide',
        ],
        'autre' => [
            'Question sur les conditions générales',
            'Suggestion d’amélioration',
            'Demande de partenariat',
            'Horaires du service client',
            'Réclamation concernant un échange',
        ],
    ];

    private const DESCRIPTIONS = [
        "Bonjour,\n\nJe rencontre ce problème depuis hier et je ne trouve pas de solution dans l'aide en ligne. Pouvez-vous m'indiquer la marche à suivre ?\n\nMerci d'avance.",
        "Bonjour,\n\nMalgré plusieurs essais, la situation ne s'améliore pas. J'ai vidé le cache de mon navigateur et redémarré mon ordinateur, sans effet.\n\nCordialement.",
        "Bonjour,\n\nC'est la deuxième fois que cela arrive ce mois-ci. Pourriez-vous vérifier ce qui se passe de votre côté ?\n\nBonne journée.",
        "Bonjour,\n\nJe vous contacte au sujet de ma dernière commande. Merci de revenir vers moi rapidement, c'est assez urgent.\n\nCordialement.",
        "Bonjour,\n\nPouvez-vous m'aider ? Je joins toutes les informations utiles et reste disponible si vous avez besoin de précisions.\n\nMerci.",
    ];

    private const COMMENTS = [
        'client' => [
            'Merci pour votre retour, le problème persiste de mon côté.',
            'Voici des précisions : cela arrive surtout le matin.',
            'Avez-vous des nouvelles concernant ma demande ?',
            'J’ai essayé votre solution, je vous tiens au courant.',
        ],
        'agent' => [
            'Bonjour, nous avons bien reçu votre demande et nous l’analysons.',
            'Pouvez-vous nous préciser la date et l’heure de l’incident ?',
            'Le correctif a été appliqué, pouvez-vous vérifier de votre côté ?',
            'Nous avons transmis votre dossier au service concerné.',
        ],
        'internal' => [
            'Vérifié dans les logs : erreur côté prestataire de paiement.',
            'Client déjà concerné par un incident similaire le mois dernier.',
            'À escalader à l’équipe technique si pas résolu sous 48 h.',
        ],
    ];

    public function run(): void
    {
        $clients = User::role(UserRole::Client)->get();
        $agents = User::role(UserRole::Agent)->get();
        $categories = Category::all();

        foreach (range(1, self::TICKET_COUNT) as $i) {
            $this->createTicket($clients->random(), $agents, $categories->random());
        }
    }

    /**
     * @param  Collection<int, User>  $agents
     */
    private function createTicket(User $client, Collection $agents, Category $category): void
    {
        $createdAt = Carbon::now()->subDays(random_int(0, 29))->subMinutes(random_int(0, 1_439));
        $status = fake()->randomElement([
            TicketStatus::Open, TicketStatus::Open,
            TicketStatus::InProgress, TicketStatus::InProgress,
            TicketStatus::Resolved, TicketStatus::Resolved,
            TicketStatus::Closed, TicketStatus::Closed, TicketStatus::Closed,
        ]);

        // Keep every date in the past: a ticket too recent to be resolved stays in progress.
        $resolvedAt = $createdAt->copy()->addHours(random_int(2, 96));
        if ($status === TicketStatus::Resolved || $status === TicketStatus::Closed) {
            if ($resolvedAt->isFuture()) {
                $status = TicketStatus::InProgress;
            }
        }

        $closedAt = $resolvedAt->copy()->addDays(random_int(1, 7));
        if ($status === TicketStatus::Closed && $closedAt->isFuture()) {
            $status = TicketStatus::Resolved;
        }

        $agent = $status === TicketStatus::Open && fake()->boolean() ? null : $agents->random();

        $ticket = Ticket::factory()
            ->for($client, 'client')
            ->for($category)
            ->create([
                'title' => fake()->randomElement(self::TITLES[$category->slug]),
                'description' => fake()->randomElement(self::DESCRIPTIONS),
                'status' => $status,
                'priority' => fake()->randomElement(TicketPriority::cases()),
                'agent_id' => $agent?->getKey(),
                'resolved_at' => in_array($status, [TicketStatus::Resolved, TicketStatus::Closed]) ? $resolvedAt : null,
                'closed_at' => $status === TicketStatus::Closed ? $closedAt : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

        $lastActivity = $this->createComments($ticket, $client, $agent);

        // updated_at is the "last activity" date used by tickets:close-stale.
        $ticket->forceFill([
            'updated_at' => collect([$lastActivity, $ticket->resolved_at, $ticket->closed_at])->filter()->max(),
        ])->saveQuietly();
    }

    /**
     * Adds a short conversation and returns the date of the last comment.
     */
    private function createComments(Ticket $ticket, User $client, ?User $agent): Carbon
    {
        $date = $ticket->created_at->copy();
        $lastCommentAt = $date;
        $end = $ticket->resolved_at ?? now();

        if ($agent === null) {
            return $lastCommentAt;
        }

        // Comments are backdated, so they must not touch the ticket's updated_at,
        // and demo data must not queue notification e-mails (CommentObserver).
        Comment::withoutEvents(fn () => Comment::withoutTouching(function () use ($ticket, $client, $agent, $end, &$date, &$lastCommentAt) {
            // A plausible conversation: the agent opens with a public answer, then client and
            // agent alternate; later agent turns are sometimes internal notes. No sentence repeats.
            $bodies = array_map(fn (array $pool) => collect($pool)->shuffle()->all(), self::COMMENTS);

            foreach (range(0, random_int(0, 3)) as $turn) {
                $date = $date->copy()->addMinutes(random_int(30, 600));
                if ($date->greaterThan($end)) {
                    break;
                }

                $byAgent = $turn % 2 === 0;
                $isInternal = $byAgent && $turn > 0 && fake()->boolean(40);
                $author = $byAgent ? $agent : $client;
                $pool = $isInternal ? 'internal' : ($byAgent ? 'agent' : 'client');

                Comment::factory()
                    ->for($ticket)
                    ->for($author)
                    ->create([
                        'body' => array_shift($bodies[$pool]),
                        'is_internal' => $isInternal,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                $lastCommentAt = $date;
            }
        }));

        return $lastCommentAt;
    }
}
