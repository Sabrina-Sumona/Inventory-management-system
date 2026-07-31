export interface SupplierCompany {
  id: number;
  name: string;
  code: string;
}

export interface SupplierBranch {
  id: number;
  name: string;
  code: string;
  city: string | null;
  district: string | null;
  is_head_office: boolean;
  is_active: boolean;
}

export interface SupplierUserSummary {
  id: number;
  name: string;
  email: string;
}

export type SupplierOpeningBalanceType =
  | "payable"
  | "receivable";

export interface Supplier {
  id: number;
  company_id: number;
  branch_id: number | null;

  name: string;
  code: string;
  business_name: string | null;

  email: string | null;
  phone: string | null;
  alternate_phone: string | null;
  website: string | null;

  tax_identification_number: string | null;
  trade_license_number: string | null;

  address_line_1: string | null;
  address_line_2: string | null;
  city: string | null;
  district: string | null;
  postal_code: string | null;
  country: string;

  payment_term_days: number;
  credit_limit: string;
  opening_balance: string;
  opening_balance_type: SupplierOpeningBalanceType;

  notes: string | null;
  is_active: boolean;

  company: SupplierCompany | null;
  branch: SupplierBranch | null;
  creator: SupplierUserSummary | null;
  updater: SupplierUserSummary | null;

  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface SupplierPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface SupplierListResponse {
  success: boolean;
  message: string;
  data: {
    suppliers: Supplier[];
    pagination: SupplierPagination;
  };
}

export interface SupplierResponse {
  success: boolean;
  message: string;
  data: {
    supplier: Supplier;
  };
}

export interface SupplierPayload {
  company_id?: number;
  branch_id: number | null;

  name: string;
  code: string;
  business_name: string | null;

  email: string | null;
  phone: string | null;
  alternate_phone: string | null;
  website: string | null;

  tax_identification_number: string | null;
  trade_license_number: string | null;

  address_line_1: string | null;
  address_line_2: string | null;
  city: string | null;
  district: string | null;
  postal_code: string | null;
  country: string;

  payment_term_days: number;
  credit_limit: number;
  opening_balance: number;
  opening_balance_type: SupplierOpeningBalanceType;

  notes: string | null;
  is_active: boolean;
}

export interface SupplierQuery {
  search?: string;
  branch_id?: number;
  is_active?: boolean;
  opening_balance_type?: SupplierOpeningBalanceType;

  sort_by?:
    | "name"
    | "code"
    | "created_at"
    | "updated_at";

  sort_direction?: "asc" | "desc";
  per_page?: number;
  page?: number;
}