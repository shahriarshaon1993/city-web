import {
    Briefcase,
    Building2,
    CircleHelp,
    CornerUpRight,
    FileUser,
    Handshake,
    History,
    Images,
    LayoutDashboard,
    LayoutTemplate,
    ListTree,
    Mail,
    Map,
    MapPin,
    Network,
    Newspaper,
    PhoneCall,
    Quote,
    Settings,
    TriangleAlert,
    Unlink,
    UserCog,
    Users,
    Wrench,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

// Icon names sent by App\Support\Admin\AdminNavigation (kebab-case Lucide names).
const icons: Record<string, LucideIcon> = {
    'building-2': Building2,
    'circle-help': CircleHelp,
    'corner-up-right': CornerUpRight,
    'file-user': FileUser,
    'layout-dashboard': LayoutDashboard,
    'layout-template': LayoutTemplate,
    'list-tree': ListTree,
    'map-pin': MapPin,
    'phone-call': PhoneCall,
    'triangle-alert': TriangleAlert,
    'user-cog': UserCog,
    briefcase: Briefcase,
    handshake: Handshake,
    history: History,
    images: Images,
    mail: Mail,
    map: Map,
    network: Network,
    newspaper: Newspaper,
    quote: Quote,
    settings: Settings,
    unlink: Unlink,
    users: Users,
    wrench: Wrench,
};

export function adminIcon(name: string): LucideIcon {
    return icons[name] ?? LayoutDashboard;
}
