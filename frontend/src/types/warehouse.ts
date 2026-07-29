export interface WarehouseCompany {
  id: number;
  name: string;
  code: string;
}

export interface WarehouseBranch {
  id: number;
  name: string;
  code: string;
  is_head_office: boolean;
}

export interface Warehouse {
  id: number;
  company_id: number;
  branch_id: number;
  name: string;
  code: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  district: string | null;
  postal_code: string | null;
  is_primary: boolean;
  is_active: boolean;
  company: WarehouseCompany;
  branch: WarehouseBranch;
  locations_count: number;
  users_count: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface WarehousePagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface WarehouseListResponse {
  success: boolean;
  message: string;
  data: {
    warehouses: Warehouse[];
    pagination: WarehousePagination;
  };
}

export interface WarehouseResponse {
  success: boolean;
  message: string;
  data: {
    warehouse: Warehouse;
  };
}

export interface WarehouseQuery {
  search?: string;
  branch_id?: number;
  is_active?: boolean;
  is_primary?: boolean;
  sort_by?:
    | "name"
    | "code"
    | "city"
    | "district"
    | "created_at"
    | "updated_at";
  sort_direction?: "asc" | "desc";
  page?: number;
  per_page?: number;
}

export interface WarehousePayload {
  branch_id: number;
  name: string;
  code: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  district: string | null;
  postal_code: string | null;
  is_primary: boolean;
  is_active: boolean;
}