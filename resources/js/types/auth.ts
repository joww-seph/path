export type Role = 'tourist' | 'partner' | 'tourism_officer' | 'admin';

export type User = {
    id: number;
    name: string;
    email: string;
    role: Role;
    phone: string | null;
    phone_verified_at: string | null;
    locale: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
