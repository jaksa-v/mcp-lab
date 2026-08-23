<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\Status;
use App\Enums\Visibility;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class LabSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed Northwind Support with enough rows that tools return something.
     */
    public function run(): void
    {
        $teams = $this->seedTeams();
        $people = $this->seedPeople($teams);

        $this->seedTickets($teams, $people);
        $this->seedArticles();
        $this->seedBadge();
    }

    /**
     * @return array<string, Team>
     */
    private function seedTeams(): array
    {
        $teams = [];

        foreach ([
            ['slug' => 'support', 'name' => 'Support'],
            ['slug' => 'billing', 'name' => 'Billing'],
            ['slug' => 'warehouse', 'name' => 'Warehouse'],
        ] as $team) {
            $teams[$team['slug']] = Team::query()->updateOrCreate(
                ['slug' => $team['slug']],
                ['name' => $team['name']],
            );
        }

        return $teams;
    }

    /**
     * @param  array<string, Team>  $teams
     * @return Collection<int, User>
     */
    private function seedPeople(array $teams): Collection
    {
        $people = [];

        foreach ([
            ['name' => 'Ada Lovelace', 'email' => 'ada@northwind.test', 'role' => Role::Requester, 'team' => 'support', 'title' => 'Customer', 'on_call' => false],
            ['name' => 'Sam Carter', 'email' => 'sam@northwind.test', 'role' => Role::Agent, 'team' => 'support', 'title' => 'Support agent', 'on_call' => false],
            ['name' => 'Root', 'email' => 'root@northwind.test', 'role' => Role::Admin, 'team' => 'support', 'title' => 'Helpdesk lead', 'on_call' => false],
            ['name' => 'Priya Shah', 'email' => 'priya@northwind.test', 'role' => Role::Requester, 'team' => 'billing', 'title' => 'Accounts payable', 'on_call' => false],
            ['name' => 'Jonah Hale', 'email' => 'jonah@northwind.test', 'role' => Role::Agent, 'team' => 'support', 'title' => 'On-call agent', 'on_call' => true],
            ['name' => 'Mei Chen', 'email' => 'mei@northwind.test', 'role' => Role::Requester, 'team' => 'warehouse', 'title' => 'Dock clerk', 'on_call' => false],
            ['name' => 'Luis Ortega', 'email' => 'luis@northwind.test', 'role' => Role::Agent, 'team' => 'billing', 'title' => 'Billing specialist', 'on_call' => false],
            ['name' => 'Nora Blake', 'email' => 'nora@northwind.test', 'role' => Role::Requester, 'team' => 'warehouse', 'title' => 'Receiving', 'on_call' => false],
        ] as $person) {
            $people[] = User::query()->updateOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'role' => $person['role'],
                    'team_id' => $teams[$person['team']]->id,
                    'title' => $person['title'],
                    'on_call' => $person['on_call'],
                ],
            );
        }

        return collect($people);
    }

    /**
     * @param  array<string, Team>  $teams
     * @param  Collection<int, User>  $people
     */
    private function seedTickets(array $teams, Collection $people): void
    {
        Comment::query()->delete();
        Ticket::query()->delete();

        $requesters = $people->filter(fn (User $user): bool => $user->role === Role::Requester)->values();
        $staff = $people->filter(fn (User $user): bool => $user->role->isStaff())->values();
        $teamList = array_values($teams);
        $statuses = Status::cases();
        $priorities = Priority::cases();

        $tickets = Ticket::factory()
            ->count(20)
            ->state(new Sequence(fn (Sequence $sequence): array => [
                'status' => $statuses[$sequence->index % count($statuses)],
                'priority' => $priorities[$sequence->index % count($priorities)],
                'requester_id' => $requesters[$sequence->index % $requesters->count()]->id,
                'assignee_id' => $sequence->index % 4 === 0
                    ? null
                    : $staff[$sequence->index % $staff->count()]->id,
                'team_id' => $teamList[$sequence->index % count($teamList)]->id,
            ]))
            ->create();

        Comment::factory()
            ->count(30)
            ->state(new Sequence(fn (Sequence $sequence): array => [
                'ticket_id' => $tickets[$sequence->index % $tickets->count()]->id,
                'user_id' => $people[$sequence->index % $people->count()]->id,
            ]))
            ->create();
    }

    private function seedArticles(): void
    {
        foreach ([
            [
                'slug' => 'refunds',
                'title' => 'Refunds',
                'visibility' => Visibility::Public,
                'body' => "Refunds take 5 to 7 business days after the warehouse confirms the return.\nAsk for the order number before promising a date.",
            ],
            [
                'slug' => 'shipping',
                'title' => 'Shipping',
                'visibility' => Visibility::Public,
                'body' => "Ground shipping is 3 to 5 days. Expedited is overnight inside the continental US.\nWarehouse cutoff is 2pm local.",
            ],
            [
                'slug' => 'escalation',
                'title' => 'Escalation',
                'visibility' => Visibility::Internal,
                'body' => "Escalate to the on-call agent after two failed contacts or any chargeback threat.\nPage Root if a reporter mentions legal counsel.",
            ],
            [
                'slug' => 'refund-abuse',
                'title' => 'Refund abuse',
                'visibility' => Visibility::Internal,
                'body' => "More than two refunds in 90 days is a review. Do not tell the requester this policy exists.\nTag Billing and leave the ticket pending.",
            ],
        ] as $article) {
            Article::query()->updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'title' => $article['title'],
                    'body' => $article['body'],
                    'visibility' => $article['visibility'],
                ],
            );
        }
    }

    private function seedBadge(): void
    {
        Storage::disk('local')->put(
            'badge.png',
            (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true),
        );
    }
}
