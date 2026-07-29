import { api } from "@/lib/api";

import type {
  Warehouse,
  WarehouseListResponse,
  WarehousePayload,
  WarehouseQuery,
  WarehouseResponse,
} from "@/types/warehouse";

function cleanQuery(
  query: WarehouseQuery
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

export const warehouseService = {
  async getWarehouses(
    query: WarehouseQuery = {}
  ): Promise<WarehouseListResponse["data"]> {
    const response =
      await api.get<WarehouseListResponse>(
        "/api/warehouses",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getWarehouse(
    warehouseId: number
  ): Promise<Warehouse> {
    const response =
      await api.get<WarehouseResponse>(
        `/api/warehouses/${warehouseId}`
      );

    return response.data.data.warehouse;
  },

  async createWarehouse(
    payload: WarehousePayload
  ): Promise<Warehouse> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.post<WarehouseResponse>(
        "/api/warehouses",
        payload
      );

    return response.data.data.warehouse;
  },

  async updateWarehouse(
    warehouseId: number,
    payload: Partial<WarehousePayload>
  ): Promise<Warehouse> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.patch<WarehouseResponse>(
        `/api/warehouses/${warehouseId}`,
        payload
      );

    return response.data.data.warehouse;
  },

  async deleteWarehouse(
    warehouseId: number
  ): Promise<void> {
    await api.get("/sanctum/csrf-cookie");

    await api.delete(
      `/api/warehouses/${warehouseId}`
    );
  },

  async restoreWarehouse(
    warehouseId: number
  ): Promise<Warehouse> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.post<WarehouseResponse>(
        `/api/warehouses/${warehouseId}/restore`
      );

    return response.data.data.warehouse;
  },
};