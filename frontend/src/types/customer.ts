export interface CustomerCompany {
  id: number;
  name: string;
  code: string;
}

export interface CustomerBranch {
  id: number;
  name: string;
  code: string;
  city: string | null;
  district: string | null;
  is_head_office: boolean;
  is_active: boolean;
}

export interface CustomerUserSummary {
  id: number;
  name: string;
  email: string;
}

export type CustomerType =
  | "retail"
  | "wholesale"
  | "corporate"
  | "government"
  | "dealer"
  | "distributor"
  | "other";

export type CustomerOpeningBalanceType =
  | "receivable"
  | "payable";

export interface Customer {
  id: number;
  company_id: number;
  branch_id: number | null;

  name: string;
  code: string;
  business_name: string | null;
  customer_type: CustomerType;

  email: string | null;
  phone: string | null;
  alternate_phone: string | null;
  website: string | null;

  tax_identification_number: string | null;
  trade_license_number: string | null;

  billing_address_line_1: string | null;
  billing_address_line_2: string | null;
  billing_city: string | null;
  billing_district: string | null;
  billing_postal_code: string | null;
  billing_country: string;

  shipping_address_line_1: string | null;
  shipping_address_line_2: string | null;
  shipping_city: string | null;
  shipping_district: string | null;
  shipping_postal_code: string | null;
  shipping_country: string;

  payment_term_days: number;
  credit_limit: string;
  opening_balance: string;
  opening_balance_type: CustomerOpeningBalanceType;

  notes: string | null;
  is_active: boolean;

  company: CustomerCompany | null;
  branch: CustomerBranch | null;
  creator: CustomerUserSummary | null;
  updater: CustomerUserSummary | null;

  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface CustomerPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface CustomerListResponse {
  success: boolean;
  message: string;
  data: {
    customers: Customer[];
    pagination: CustomerPagination;
  };
}

export interface CustomerResponse {
  success: boolean;
  message: string;
  data: {
    customer: Customer;
  };
}

export interface CustomerPayload {
  company_id?: number;
  branch_id: number | null;

  name: string;
  code: string;
  business_name: string | null;
  customer_type: CustomerType;

  email: string | null;
  phone: string | null;
  alternate_phone: string | null;
  website: string | null;

  tax_identification_number: string | null;
  trade_license_number: string | null;

  billing_address_line_1: string | null;
  billing_address_line_2: string | null;
  billing_city: string | null;
  billing_district: string | null;
  billing_postal_code: string | null;
  billing_country: string;

  shipping_address_line_1: string | null;
  shipping_address_line_2: string | null;
  shipping_city: string | null;
  shipping_district: string | null;
  shipping_postal_code: string | null;
  shipping_country: string;

  payment_term_days: number;
  credit_limit: number;
  opening_balance: number;
  opening_balance_type: CustomerOpeningBalanceType;

  notes: string | null;
  is_active: boolean;
}

export interface CustomerQuery {
  search?: string;
  branch_id?: number;
  customer_type?: CustomerType;
  is_active?: boolean;
  opening_balance_type?: CustomerOpeningBalanceType;

  sort_by?:
    | "name"
    | "code"
    | "customer_type"
    | "created_at"
    | "updated_at";

  sort_direction?: "asc" | "desc";
  per_page?: number;
  page?: number;
}

export type CustomerContactType =
  | "general"
  | "sales"
  | "accounts"
  | "management"
  | "support"
  | "purchase"
  | "other";

export interface CustomerSummary {
  id: number;
  company_id: number;
  branch_id: number | null;
  name: string;
  code: string;
  business_name: string | null;
  customer_type: CustomerType;
  is_active: boolean;
}

export interface CustomerContact {
  id: number;
  customer_id: number;

  name: string;
  designation: string | null;
  department: string | null;
  contact_type: CustomerContactType;

  email: string | null;
  phone: string | null;
  alternate_phone: string | null;

  is_primary: boolean;
  is_active: boolean;

  notes: string | null;

  customer: CustomerSummary | null;
  creator: CustomerUserSummary | null;
  updater: CustomerUserSummary | null;

  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface CustomerContactPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface CustomerContactListResponse {
  success: boolean;
  message: string;
  data: {
    customer_contacts: CustomerContact[];
    pagination: CustomerContactPagination;
  };
}

export interface CustomerContactResponse {
  success: boolean;
  message: string;
  data: {
    customer_contact: CustomerContact;
  };
}

export interface CustomerContactPayload {
  customer_id: number;

  name: string;
  designation: string | null;
  department: string | null;
  contact_type: CustomerContactType;

  email: string | null;
  phone: string | null;
  alternate_phone: string | null;

  is_primary: boolean;
  is_active: boolean;

  notes: string | null;
}

export interface CustomerContactUpdatePayload {
  name?: string;
  designation?: string | null;
  department?: string | null;
  contact_type?: CustomerContactType;

  email?: string | null;
  phone?: string | null;
  alternate_phone?: string | null;

  is_primary?: boolean;
  is_active?: boolean;

  notes?: string | null;
}

export interface CustomerContactQuery {
  customer_id?: number;
  search?: string;
  contact_type?: CustomerContactType;
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

export type CustomerPaymentMethod =
  | "cash"
  | "bank_transfer"
  | "mobile_banking"
  | "cheque"
  | "card"
  | "credit";

export type CustomerSalesPriceBasis =
  | "exclusive_of_tax"
  | "inclusive_of_tax";

export type CustomerSalesOrderTerm =
  | "standard"
  | "advance_payment"
  | "cash_on_delivery"
  | "partial_advance"
  | "credit";

export interface CustomerFinancialSetting {
  id: number;
  customer_id: number;

  currency_code: string;
  default_payment_method: CustomerPaymentMethod;

  payment_term_days: number;
  credit_limit: string;

  allow_credit_sale: boolean;
  block_sale_on_credit_limit: boolean;

  default_sales_discount_percent: string;

  is_tax_applicable: boolean;
  default_tax_percent: string;

  is_withholding_tax_applicable: boolean;
  withholding_tax_percent: string;

  sales_price_basis: CustomerSalesPriceBasis;
  default_sales_order_term: CustomerSalesOrderTerm;

  payment_instruction: string | null;
  notes: string | null;

  is_active: boolean;

  customer: CustomerSummary | null;
  creator: CustomerUserSummary | null;
  updater: CustomerUserSummary | null;

  created_at: string | null;
  updated_at: string | null;
}

export interface CustomerFinancialSettingPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface CustomerFinancialSettingListResponse {
  success: boolean;
  message: string;
  data: {
    customer_financial_settings:
      CustomerFinancialSetting[];

    pagination:
      CustomerFinancialSettingPagination;
  };
}

export interface CustomerFinancialSettingResponse {
  success: boolean;
  message: string;
  data: {
    customer_financial_setting:
      CustomerFinancialSetting;
  };
}

export interface CustomerFinancialSettingPayload {
  customer_id: number;

  currency_code: string;
  default_payment_method: CustomerPaymentMethod;

  payment_term_days: number;
  credit_limit: number;

  allow_credit_sale: boolean;
  block_sale_on_credit_limit: boolean;

  default_sales_discount_percent: number;

  is_tax_applicable: boolean;
  default_tax_percent: number;

  is_withholding_tax_applicable: boolean;
  withholding_tax_percent: number;

  sales_price_basis: CustomerSalesPriceBasis;
  default_sales_order_term: CustomerSalesOrderTerm;

  payment_instruction: string | null;
  notes: string | null;

  is_active: boolean;
}

export type CustomerFinancialSettingUpdatePayload =
  Partial<
    Omit<
      CustomerFinancialSettingPayload,
      "customer_id"
    >
  >;

export interface CustomerFinancialSettingQuery {
  customer_id?: number;
  search?: string;

  currency_code?: string;

  default_payment_method?:
    CustomerPaymentMethod;

  allow_credit_sale?: boolean;
  is_tax_applicable?: boolean;
  is_active?: boolean;

  sort_by?:
    | "customer_id"
    | "currency_code"
    | "credit_limit"
    | "payment_term_days"
    | "created_at"
    | "updated_at";

  sort_direction?: "asc" | "desc";
  per_page?: number;
  page?: number;
}