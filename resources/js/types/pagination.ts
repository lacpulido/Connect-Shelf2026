import type { User} from './user';
import type {Role}from './common'

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedUsers = {
    data: (User & { roles?: Role[] })[];
    links: PaginationLink[];
};