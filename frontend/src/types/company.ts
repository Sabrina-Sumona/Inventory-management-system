export interface Company {
  id: number;
  name: string;
  code: string;
  email: string | null;
  website: string | null;
  phone: string | null;
  address: string | null;
  timezone: string;
  currency: string;
  is_active: boolean;
  branches_count: number;
  warehouses_count: number;
  users_count: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface CompanyListResponse {
  success: boolean;
  message: string;
  data: {
    companies: Company[];
  };
}

export interface CompanyResponse {
  success: boolean;
  message: string;
  data: {
    company: Company;
  };
}

export interface UpdateCompanyPayload {
  name: string;
  email: string | null;
  website: string | null;
  phone: string | null;
  address: string | null;
  timezone: string;
  currency: string;
}