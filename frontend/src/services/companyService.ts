import { api } from "@/lib/api";
import type {
  Company,
  CompanyListResponse,
  CompanyResponse,
  UpdateCompanyPayload,
} from "@/types/company";

export const companyService = {
  async getAccessibleCompanies(): Promise<Company[]> {
    const response =
      await api.get<CompanyListResponse>(
        "/api/companies"
      );

    return response.data.data.companies;
  },

  async getCompany(
    companyId: number
  ): Promise<Company> {
    const response =
      await api.get<CompanyResponse>(
        `/api/companies/${companyId}`
      );

    return response.data.data.company;
  },

  async updateCompany(
    companyId: number,
    payload: UpdateCompanyPayload
  ): Promise<Company> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.patch<CompanyResponse>(
        `/api/companies/${companyId}`,
        payload
      );

    return response.data.data.company;
  },
};