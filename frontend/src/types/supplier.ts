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

export type SupplierContactType =
  | "general"
  | "sales"
  | "accounts"
  | "support"
  | "management";

export interface SupplierSummary {
  id: number;
  company_id: number;
  branch_id: number | null;
  name: string;
  code: string;
  business_name: string | null;
  is_active: boolean;
}

export interface SupplierContact {
  id: number;
  supplier_id: number;

  name: string;
  designation: string | null;
  department: string | null;
  contact_type: SupplierContactType;

  email: string | null;
  phone: string | null;
  alternate_phone: string | null;

  is_primary: boolean;
  is_active: boolean;

  notes: string | null;

  supplier: SupplierSummary | null;
  creator: SupplierUserSummary | null;
  updater: SupplierUserSummary | null;

  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface SupplierContactPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface SupplierContactListResponse {
  success: boolean;
  message: string;
  data: {
    supplier_contacts: SupplierContact[];
    pagination: SupplierContactPagination;
  };
}

export interface SupplierContactResponse {
  success: boolean;
  message: string;
  data: {
    supplier_contact: SupplierContact;
  };
}

export interface SupplierContactPayload {
  supplier_id: number;

  name: string;
  designation: string | null;
  department: string | null;
  contact_type: SupplierContactType;

  email: string | null;
  phone: string | null;
  alternate_phone: string | null;

  is_primary: boolean;
  is_active: boolean;

  notes: string | null;
}

export interface SupplierContactUpdatePayload {
  name?: string;
  designation?: string | null;
  department?: string | null;
  contact_type?: SupplierContactType;

  email?: string | null;
  phone?: string | null;
  alternate_phone?: string | null;

  is_primary?: boolean;
  is_active?: boolean;

  notes?: string | null;
}

export interface SupplierContactQuery {
  supplier_id?: number;
  search?: string;
  contact_type?: SupplierContactType;
  is_primary?: boolean;
  is_active?: boolean;

  sort_by?:
    | "name"
    | "contact_type"
    | "created_at"
    | "updated_at";

  sort_direction?: "asc" | "desc";
  per_page?: number;
  page?: number;
}

export type SupplierPaymentMethod =
  | "cash"
  | "bank_transfer"
  | "cheque"
  | "mobile_banking"
  | "credit";

export type SupplierPurchasePriceBasis =
  | "inclusive_of_tax"
  | "exclusive_of_tax";

export type SupplierPurchaseOrderTerm =
  | "standard"
  | "advance_payment"
  | "partial_advance"
  | "cash_on_delivery"
  | "credit";

export interface SupplierFinancialSetting {
  id: number;
  supplier_id: number;

  currency_code: string;
  default_payment_method: SupplierPaymentMethod;

  payment_term_days: number;
  credit_limit: string;

  allow_credit_purchase: boolean;
  block_purchase_on_credit_limit: boolean;

  default_purchase_discount_percent: string;

  is_tax_applicable: boolean;
  default_tax_percent: string;

  is_withholding_tax_applicable: boolean;
  withholding_tax_percent: string;

  purchase_price_basis: SupplierPurchasePriceBasis;
  default_purchase_order_term: SupplierPurchaseOrderTerm;

  payment_instruction: string | null;
  notes: string | null;

  is_active: boolean;

  supplier: SupplierSummary | null;
  creator: SupplierUserSummary | null;
  updater: SupplierUserSummary | null;

  created_at: string | null;
  updated_at: string | null;
}

export interface SupplierFinancialSettingPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface SupplierFinancialSettingListResponse {
  success: boolean;
  message: string;
  data: {
    supplier_financial_settings:
      SupplierFinancialSetting[];

    pagination:
      SupplierFinancialSettingPagination;
  };
}

export interface SupplierFinancialSettingResponse {
  success: boolean;
  message: string;
  data: {
    supplier_financial_setting:
      SupplierFinancialSetting;
  };
}

export interface SupplierFinancialSettingPayload {
  supplier_id: number;

  currency_code: string;
  default_payment_method: SupplierPaymentMethod;

  payment_term_days: number;
  credit_limit: number;

  allow_credit_purchase: boolean;
  block_purchase_on_credit_limit: boolean;

  default_purchase_discount_percent: number;

  is_tax_applicable: boolean;
  default_tax_percent: number;

  is_withholding_tax_applicable: boolean;
  withholding_tax_percent: number;

  purchase_price_basis: SupplierPurchasePriceBasis;
  default_purchase_order_term: SupplierPurchaseOrderTerm;

  payment_instruction: string | null;
  notes: string | null;

  is_active: boolean;
}

export type SupplierFinancialSettingUpdatePayload =
  Partial<
    Omit<
      SupplierFinancialSettingPayload,
      "supplier_id"
    >
  >;

export interface SupplierFinancialSettingQuery {
  supplier_id?: number;
  search?: string;

  currency_code?: string;

  default_payment_method?:
    SupplierPaymentMethod;

  allow_credit_purchase?: boolean;
  is_tax_applicable?: boolean;

  is_withholding_tax_applicable?:
    boolean;

  is_active?: boolean;

  sort_by?:
    | "currency_code"
    | "credit_limit"
    | "payment_term_days"
    | "created_at"
    | "updated_at";

  sort_direction?: "asc" | "desc";
  per_page?: number;
  page?: number;
}