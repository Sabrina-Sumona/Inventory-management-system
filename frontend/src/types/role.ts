export interface Role {
  id: number;
  company_id: number | null;
  name: string;
  code: string;
  description: string | null;
  is_system: boolean;
  is_active: boolean;
}

export interface RoleListResponse {
  success: boolean;
  message: string;
  data: {
    roles: Role[];
  };
}

export interface RoleQuery {
  company_id?: number;
}