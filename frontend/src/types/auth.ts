export interface AuthUser {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    user: AuthUser;
  };
}

export interface CurrentUserResponse {
  success: boolean;
  message: string;
  data: {
    user: AuthUser;
  };
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