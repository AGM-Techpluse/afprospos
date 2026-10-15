<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateCollectionCaseEventTypes(includeTransfer: true);

        $guard = 'staff';
        $permissions = ['repairs.transfer', 'collection.transfer'];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $this->givePermissions('Shop Owner', $permissions);
        $this->givePermissions('Manager', ['repairs.transfer', 'collection.transfer']);
        $this->givePermissions('Cashier', ['repairs.transfer', 'collection.view', 'collection.process', 'collection.transfer']);
    }

    public function down(): void
    {
        DB::table('collection_case_events')->where('event_type', 'collection_transferred')->delete();
        $this->updateCollectionCaseEventTypes(includeTransfer: false);

        foreach (['Shop Owner', 'Manager'] as $roleName) {
            $role = Role::query()
                ->where('guard_name', 'staff')
                ->where('name', $roleName)
                ->first();

            $role?->revokePermissionTo(['repairs.transfer', 'collection.transfer']);
        }

        $cashier = Role::query()
            ->where('guard_name', 'staff')
            ->where('name', 'Cashier')
            ->first();

        $cashier?->revokePermissionTo(['repairs.transfer', 'collection.view', 'collection.process', 'collection.transfer']);

        Permission::query()
            ->where('guard_name', 'staff')
            ->whereIn('name', ['repairs.transfer', 'collection.transfer'])
            ->delete();
    }

    /** @param string[] $permissions */
    private function givePermissions(string $roleName, array $permissions): void
    {
        $role = Role::query()
            ->where('guard_name', 'staff')
            ->where('name', $roleName)
            ->first();

        if ($role === null) {
            return;
        }

        $role->givePermissionTo($permissions);
    }

    private function updateCollectionCaseEventTypes(bool $includeTransfer): void
    {
        $types = [
            'deadline_set',
            'notified',
            'extended',
            'fee_accrued',
            'marked_overdue',
            'marked_abandoned',
            'administrative_resolution',
        ];

        if ($includeTransfer) {
            $types[] = 'collection_transferred';
        }

        $quotedTypes = implode("','", $types);
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE collection_case_events MODIFY event_type ENUM('{$quotedTypes}') NOT NULL");

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys=off');
        DB::statement("CREATE TABLE collection_case_events_rebuild (
            id integer primary key autoincrement not null,
            collection_case_id integer not null,
            event_type varchar not null check (event_type in ('{$quotedTypes}')),
            actor_staff_id integer,
            detail text not null,
            created_at datetime not null default CURRENT_TIMESTAMP,
            foreign key(collection_case_id) references collection_cases(id) on delete cascade on update restrict,
            foreign key(actor_staff_id) references staff(id) on delete set null on update restrict
        )");
        DB::statement('INSERT INTO collection_case_events_rebuild (id, collection_case_id, event_type, actor_staff_id, detail, created_at)
            SELECT id, collection_case_id, event_type, actor_staff_id, detail, created_at FROM collection_case_events');
        DB::statement('DROP TABLE collection_case_events');
        DB::statement('ALTER TABLE collection_case_events_rebuild RENAME TO collection_case_events');
        DB::statement('CREATE INDEX collection_case_events_case_idx ON collection_case_events (collection_case_id)');
        DB::statement('PRAGMA foreign_keys=on');
    }
};
