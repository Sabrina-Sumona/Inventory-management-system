export interface UserCompany {
  id: number;
  name: string;
  code: string;
}

export interface UserRole {
  id: number;
  name: string;
  code: string;
}

export interface User {
  id: number;
  company_id: number | null;
  name: string;
  email: string;
  company: UserCompany | null;
  roles: UserRole[];
  branches_count: number;
  warehouses_count: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface UserPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface UserListResponse {
  success: boolean;
  message: string;
  data: {
    users: User[];
    pagination: UserPagination;
  };
}

export interface UserResponse {
  success: boolean;
  message: string;
  data: {
    user: User;
  };
}

export interface UserMutationResponse {
  success: boolean;
  message: string;
  data: {
    user: User;
  };
}

export interface UserQuery {
  search?: string;

  sort_by?:
    | "name"
    | "email"
    | "created_at"
    | "updated_at";

  sort_direction?: "asc" | "desc";

  per_page?: number;

  page?: number;
}

export interface CreateUserPayload {
  company_id?: number | null;
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role_ids: number[];
}

export interface UpdateUserPayload {
  company_id?: number | null;
  name: string;
  email: string;
  role_ids: number[];
}