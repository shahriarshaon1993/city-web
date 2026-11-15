/** One row of the users table (App\Http\Resources\Admin\UserRowResource). */
export type UserRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: string | null;
    status: 'active' | 'invited' | 'inactive';
    is_active: boolean;
    is_self: boolean;
    last_login_at: string | null;
    created_at: string | null;
    can: {
        edit: boolean;
        resend: boolean;
        toggle: boolean;
        delete_visible: boolean;
        delete: boolean;
    };
    blocked: {
        edit: string | null;
        toggle: string | null;
        delete: string | null;
    };
};

/** One row of the roles table (RoleRowResource). */
export type RoleRow = {
    id: number;
    name: string;
    type: 'system' | 'custom';
    users_count: number;
    permissions_count: number;
    created_at: string | null;
    can: { edit: boolean; delete: boolean };
    blocked: { edit: string | null; delete: string | null };
};

export type PermissionGroup = {
    label: string;
    permissions: Array<{ name: string; label: string }>;
};

/** One row of the activity log (ActivityRowResource). */
export type ActivityRow = {
    id: number;
    created_at: string | null;
    who: { name: string; role: string | null; deleted: boolean } | null;
    action: { label: string; tone: string };
    log: string | null;
    description: string | null;
    subject: { type: string; label: string; missing: boolean } | null;
    ip: string | null;
    user_agent: string | null;
    changes: Array<{ field: string; old: string | null; new: string | null }>;
    details: Array<{ key: string; value: string }>;
};

/** What a restore needs decided first (App\Support\RecycleBin\RestoreConflict, DEL-05). */
export type RestoreConflict = {
    field: string;
    kind: 'slug' | 'parent';
    message: string;
    suggestion: string | null;
};

/** One row of the Recycle bin table (RecycleBinRowResource, DEL-04). `purge_at` null = never auto-deleted. */
export type RecycleBinRow = {
    id: number;
    label: string;
    thumbnail: string | null;
    type: string;
    type_label: string;
    deleted_by: string | null;
    deleted_at: string;
    purge_at: string | null;
    url: string | null;
    conflicts: RestoreConflict[];
    can: { restore: boolean; forever: boolean };
};
