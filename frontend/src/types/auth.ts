export interface CompanySummary {
  id: number;
  name: string;
  code: string;
}

export interface RoleSummary {
  id: number;
  name: string;
  code: string;
}

export interface PermissionSummary {
  name: string;
  code: string;
  module: string;
  action: string;
}

export interface BranchSummary {
  id: number;
  name: string;
  code: string;
  is_primary: boolean;
}

export interface WarehouseSummary {
  id: number;
  name: string;
  code: string;
  branch_id: number;
  is_primary: boolean;
}

export interface LoginUser {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
}

export interface AuthUser extends LoginUser {
  company: CompanySummary | null;
  roles: RoleSummary[];
  permissions: PermissionSummary[];
  branches: BranchSummary[];
  warehouses: WarehouseSummary[];
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    user: LoginUser;
  };
}

export interface CurrentUserResponse {
  success: boolean;
  message: string;
  data: {
    user: AuthUser;
  };
}

export interface ResetPasswordCredentials {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface PasswordResetResponse {
  success: boolean;
  message: string;
}

export interface ApiErrorResponse {
  success?: boolean;
  message?: string;
  errors?: Record<string, string[]>;
}