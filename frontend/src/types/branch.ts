export interface BranchCompany {
  id: number;
  name: string;
  code: string;
}

export interface Branch {
  id: number;
  company_id: number;
  name: string;
  code: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  district: string | null;
  postal_code: string | null;
  is_head_office: boolean;
  is_active: boolean;
  company: BranchCompany;
  warehouses_count: number;
  users_count: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface BranchPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface BranchListResponse {
  success: boolean;
  message: string;
  data: {
    branches: Branch[];
    pagination: BranchPagination;
  };
}

export interface BranchResponse {
  success: boolean;
  message: string;
  data: {
    branch: Branch;
  };
}

export interface BranchQuery {
  search?: string;
  is_active?: boolean;
  is_head_office?: boolean;
  sort_by?: "name" | "code" | "city" | "district" | "created_at" | "updated_at";
  sort_direction?: "asc" | "desc";
  per_page?: number;
  page?: number;
}

export interface BranchPayload {
  name: string;
  code: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  district: string | null;
  postal_code: string | null;
  is_head_office: boolean;
  is_active: boolean;
}