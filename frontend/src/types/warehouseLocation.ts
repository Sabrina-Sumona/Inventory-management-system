export type WarehouseLocationType =
  | "zone"
  | "rack"
  | "shelf"
  | "bin";

export interface WarehouseLocationCompany {
  id: number;
  name: string;
  code: string;
}

export interface WarehouseLocationBranch {
  id: number;
  name: string;
  code: string;
}

export interface WarehouseLocationWarehouse {
  id: number;
  name: string;
  code: string;
  is_primary: boolean;
}

export interface WarehouseLocationParent {
  id: number;
  name: string;
  code: string;
  type: WarehouseLocationType;
}

export interface WarehouseLocation {
  id: number;
  company_id: number;
  branch_id: number;
  warehouse_id: number;
  parent_id: number | null;

  name: string;
  code: string;
  type: WarehouseLocationType;
  barcode: string | null;
  capacity: string | null;
  description: string | null;
  is_active: boolean;

  company: WarehouseLocationCompany;
  branch: WarehouseLocationBranch;
  warehouse: WarehouseLocationWarehouse;
  parent: WarehouseLocationParent | null;

  children_count: number;

  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface WarehouseLocationPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface WarehouseLocationListResponse {
  success: boolean;
  message: string;
  data: {
    locations: WarehouseLocation[];
    pagination: WarehouseLocationPagination;
  };
}

export interface WarehouseLocationResponse {
  success: boolean;
  message: string;
  data: {
    location: WarehouseLocation;
  };
}

export interface WarehouseLocationQuery {
  search?: string;
  warehouse_id?: number;
  parent_id?: number;
  type?: WarehouseLocationType;
  is_active?: boolean;
  sort_by?:
    | "name"
    | "code"
    | "type"
    | "capacity"
    | "created_at"
    | "updated_at";
  sort_direction?: "asc" | "desc";
  page?: number;
  per_page?: number;
}

export interface WarehouseLocationPayload {
  warehouse_id: number;
  parent_id: number | null;
  name: string;
  code: string;
  type: WarehouseLocationType;
  barcode: string | null;
  capacity: number | null;
  description: string | null;
  is_active: boolean;
}