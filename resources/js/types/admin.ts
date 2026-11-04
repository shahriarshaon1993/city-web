export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';
export type TableDensity = 'comfortable' | 'compact';

/** `auth.user` shared by HandleInertiaRequests::shareAuth(). */
export type AdminUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatar: string | null;
    roles: string[];
    theme: Appearance;
    table_density: TableDensity;
    email_verified_at: string | null;
};

export type AdminAuth = {
    user: AdminUser;
    permissions: string[];
};

/** One sidebar entry from AdminNavigation::forUser(); `count` is null until its phase. */
export type NavEntry = {
    key: string;
    label: string;
    href: string;
    icon: string;
    count: number | null;
};

export type NavGroup = {
    label: string;
    items: NavEntry[];
};

/** Breadcrumb from the page's `breadcrumbs` prop; `href` null = plain text. */
export type AdminCrumb = {
    title: string;
    href: string | null;
};

export type UserSession = {
    id: string;
    device: string;
    ip: string | null;
    last_active: string;
    current: boolean;
};
