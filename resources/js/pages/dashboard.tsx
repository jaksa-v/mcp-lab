import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';

type TicketStatus = 'open' | 'pending' | 'closed';
type TicketPriority = 'low' | 'normal' | 'high' | 'urgent';
type PersonRole = 'requester' | 'agent' | 'admin';

type TicketRow = {
    id: number;
    subject: string;
    status: TicketStatus;
    priority: TicketPriority;
    requester: string;
    assignee: string | null;
};

type PersonRow = {
    id: number;
    name: string;
    role: PersonRole;
    team: string | null;
    on_call: boolean;
};

export default function Dashboard({
    tickets,
    people,
}: {
    tickets: TicketRow[];
    people: PersonRow[];
}) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto p-4">
                <section className="space-y-4">
                    <Heading
                        title="Tickets"
                        description="Northwind Support dump. The agent is the write path."
                    />
                    <DumpTable
                        columns={[
                            'ID',
                            'Subject',
                            'Status',
                            'Priority',
                            'Requester',
                            'Assignee',
                        ]}
                        empty="No tickets yet."
                        isEmpty={tickets.length === 0}
                    >
                        {tickets.map((ticket) => (
                            <tr
                                key={ticket.id}
                                className="border-b border-sidebar-border/70 last:border-0"
                            >
                                <td className="px-3 py-2 text-muted-foreground">
                                    {ticket.id}
                                </td>
                                <td className="px-3 py-2 font-medium">
                                    {ticket.subject}
                                </td>
                                <td className="px-3 py-2">
                                    <Badge
                                        variant={
                                            ticket.status === 'closed'
                                                ? 'outline'
                                                : ticket.status === 'pending'
                                                  ? 'secondary'
                                                  : 'default'
                                        }
                                    >
                                        {ticket.status}
                                    </Badge>
                                </td>
                                <td className="px-3 py-2">
                                    <Badge
                                        variant={
                                            ticket.priority === 'urgent'
                                                ? 'destructive'
                                                : 'outline'
                                        }
                                    >
                                        {ticket.priority}
                                    </Badge>
                                </td>
                                <td className="px-3 py-2">{ticket.requester}</td>
                                <td className="px-3 py-2 text-muted-foreground">
                                    {ticket.assignee ?? 'Unassigned'}
                                </td>
                            </tr>
                        ))}
                    </DumpTable>
                </section>

                <section className="space-y-4">
                    <Heading
                        title="People"
                        description="Directory dump. Ticket tools should look these up through the other server."
                    />
                    <DumpTable
                        columns={['ID', 'Name', 'Role', 'Team', 'On call']}
                        empty="No people yet."
                        isEmpty={people.length === 0}
                    >
                        {people.map((person) => (
                            <tr
                                key={person.id}
                                className="border-b border-sidebar-border/70 last:border-0"
                            >
                                <td className="px-3 py-2 text-muted-foreground">
                                    {person.id}
                                </td>
                                <td className="px-3 py-2 font-medium">
                                    {person.name}
                                </td>
                                <td className="px-3 py-2">
                                    <Badge variant="secondary">
                                        {person.role}
                                    </Badge>
                                </td>
                                <td className="px-3 py-2">
                                    {person.team ?? '—'}
                                </td>
                                <td className="px-3 py-2">
                                    {person.on_call ? (
                                        <Badge>On call</Badge>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            No
                                        </span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </DumpTable>
                </section>
            </div>
        </>
    );
}

function DumpTable({
    columns,
    empty,
    isEmpty,
    children,
}: {
    columns: string[];
    empty: string;
    isEmpty: boolean;
    children: ReactNode;
}) {
    return (
        <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table className="w-full min-w-160 text-left text-sm">
                <thead className="border-b border-sidebar-border/70 bg-muted/50 text-muted-foreground">
                    <tr>
                        {columns.map((column) => (
                            <th key={column} className="px-3 py-2 font-medium">
                                {column}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {isEmpty ? (
                        <tr>
                            <td
                                className="px-3 py-6 text-muted-foreground"
                                colSpan={columns.length}
                            >
                                {empty}
                            </td>
                        </tr>
                    ) : (
                        children
                    )}
                </tbody>
            </table>
        </div>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
