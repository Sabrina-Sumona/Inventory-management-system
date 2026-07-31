import { api } from "@/lib/api";

import type {
  Supplier,
  SupplierListResponse,
  SupplierPayload,
  SupplierQuery,
  SupplierResponse,
} from "@/types/supplier";

function cleanQuery(
  query: SupplierQuery
): Record<string, string | number | boolean> {
  return Object.fromEntries(
    Object.entries(query).filter(
      ([, value]) =>
        value !== undefined &&
        value !== null &&
        value !== ""
    )
  ) as Record<string, string | number | boolean>;
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
    await api.get("/sanctum/csrf-cookie");

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
    await api.get("/sanctum/csrf-cookie");

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
    await api.get("/sanctum/csrf-cookie");

    await api.delete(
      `/api/suppliers/${supplierId}`
    );
  },

  async restoreSupplier(
    supplierId: number
  ): Promise<Supplier> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.post<SupplierResponse>(
        `/api/suppliers/${supplierId}/restore`
      );

    return response.data.data.supplier;
  },
};