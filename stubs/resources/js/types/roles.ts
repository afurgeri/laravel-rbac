export type RolePermission = {
    id: string | number;
    name: string;
    label: string;
    subject: string;
    column: 'view' | 'create' | 'update' | 'delete' | null;
    action: string;
};
