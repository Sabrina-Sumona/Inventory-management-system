import { api } from "@/lib/api";

import type {
  Supplier,
  SupplierContact,
  SupplierContactListResponse,
  SupplierContactPayload,
  SupplierContactQuery,
  SupplierContactResponse,
  SupplierContactUpdatePayload,
  SupplierFinancialSetting,
  SupplierFinancialSettingListResponse,
  SupplierFinancialSettingPayload,
  SupplierFinancialSettingQuery,
  SupplierFinancialSettingResponse,
  SupplierFinancialSettingUpdatePayload,
  SupplierListResponse,
  SupplierPayload,
  SupplierQuery,
  SupplierResponse,
} from "@/types/supplier";

type QueryValue =
  | string
  | number
  | boolean;

function cleanQuery<T extends object>(
  query: T
): Record<string, QueryValue> {
  return Object.fromEntries(
    Object.entries(query).filter(
      ([, value]) =>
        value !== undefined &&
        value !== null &&
        value !== ""
    )
  ) as Record<string, QueryValue>;
}

async function ensureCsrfCookie(): Promise<void> {
  await api.get("/sanctum/csrf-cookie");
}

export const supplierService = {
  async getSuppliers(
    query: SupplierQuery = {}
  ): Promise<SupplierListResponse["data"]> {
    const response =
      await api.get<SupplierListResponse>(
        "/api/suppliers",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getSupplier(
    supplierId: number
  ): Promise<Supplier> {
    const response =
      await api.get<SupplierResponse>(
        `/api/suppliers/${supplierId}`
      );

    return response.data.data.supplier;
  },

  async createSupplier(
    payload: SupplierPayload
  ): Promise<Supplier> {
    await ensureCsrfCookie();

    const response =
      await api.post<SupplierResponse>(
        "/api/suppliers",
        payload
      );

    return response.data.data.supplier;
  },

  async updateSupplier(
    supplierId: number,
    payload: Partial<SupplierPayload>
  ): Promise<Supplier> {
    await ensureCsrfCookie();

    const response =
      await api.patch<SupplierResponse>(
        `/api/suppliers/${supplierId}`,
        payload
      );

    return response.data.data.supplier;
  },

  async deleteSupplier(
    supplierId: number
  ): Promise<void> {
    await ensureCsrfCookie();

    await api.delete(
      `/api/suppliers/${supplierId}`
    );
  },

  async restoreSupplier(
    supplierId: number
  ): Promise<Supplier> {
    await ensureCsrfCookie();

    const response =
      await api.post<SupplierResponse>(
        `/api/suppliers/${supplierId}/restore`
      );

    return response.data.data.supplier;
  },

  async getSupplierContacts(
    query: SupplierContactQuery = {}
  ): Promise<
    SupplierContactListResponse["data"]
  > {
    const response =
      await api.get<SupplierContactListResponse>(
        "/api/supplier-contacts",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getSupplierContact(
    supplierContactId: number
  ): Promise<SupplierContact> {
    const response =
      await api.get<SupplierContactResponse>(
        `/api/supplier-contacts/${supplierContactId}`
      );

    return response.data.data
      .supplier_contact;
  },

  async createSupplierContact(
    payload: SupplierContactPayload
  ): Promise<SupplierContact> {
    await ensureCsrfCookie();

    const response =
      await api.post<SupplierContactResponse>(
        "/api/supplier-contacts",
        payload
      );

    return response.data.data
      .supplier_contact;
  },

  async updateSupplierContact(
    supplierContactId: number,
    payload: SupplierContactUpdatePayload
  ): Promise<SupplierContact> {
    await ensureCsrfCookie();

    const response =
      await api.patch<SupplierContactResponse>(
        `/api/supplier-contacts/${supplierContactId}`,
        payload
      );

    return response.data.data
      .supplier_contact;
  },

  async deleteSupplierContact(
    supplierContactId: number
  ): Promise<void> {
    await ensureCsrfCookie();

    await api.delete(
      `/api/supplier-contacts/${supplierContactId}`
    );
  },

  async restoreSupplierContact(
    supplierContactId: number
  ): Promise<SupplierContact> {
    await ensureCsrfCookie();

    const response =
      await api.post<SupplierContactResponse>(
        `/api/supplier-contacts/${supplierContactId}/restore`
      );

    return response.data.data
      .supplier_contact;
  },

  async getSupplierFinancialSettings(
    query: SupplierFinancialSettingQuery = {}
  ): Promise<
    SupplierFinancialSettingListResponse["data"]
  > {
    const response =
      await api.get<SupplierFinancialSettingListResponse>(
        "/api/supplier-financial-settings",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getSupplierFinancialSetting(
    supplierFinancialSettingId: number
  ): Promise<SupplierFinancialSetting> {
    const response =
      await api.get<SupplierFinancialSettingResponse>(
        `/api/supplier-financial-settings/${supplierFinancialSettingId}`
      );

    return response.data.data
      .supplier_financial_setting;
  },

  async createSupplierFinancialSetting(
    payload: SupplierFinancialSettingPayload
  ): Promise<SupplierFinancialSetting> {
    await ensureCsrfCookie();

    const response =
      await api.post<SupplierFinancialSettingResponse>(
        "/api/supplier-financial-settings",
        payload
      );

    return response.data.data
      .supplier_financial_setting;
  },

  async updateSupplierFinancialSetting(
    supplierFinancialSettingId: number,
    payload: SupplierFinancialSettingUpdatePayload
  ): Promise<SupplierFinancialSetting> {
    await ensureCsrfCookie();

    const response =
      await api.patch<SupplierFinancialSettingResponse>(
        `/api/supplier-financial-settings/${supplierFinancialSettingId}`,
        payload
      );

    return response.data.data
      .supplier_financial_setting;
  },

  async deleteSupplierFinancialSetting(
    supplierFinancialSettingId: number
  ): Promise<void> {
    await ensureCsrfCookie();

    await api.delete(
      `/api/supplier-financial-settings/${supplierFinancialSettingId}`
    );
  },
};