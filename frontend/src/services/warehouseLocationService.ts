import { api } from "@/lib/api";

import type {
  WarehouseLocation,
  WarehouseLocationListResponse,
  WarehouseLocationPayload,
  WarehouseLocationQuery,
  WarehouseLocationResponse,
} from "@/types/warehouseLocation";

function cleanQuery(
  query: WarehouseLocationQuery
): Record<string, string | number | boolean> {
  return Object.fromEntries(
    Object.entries(query).filter(
      ([, value]) =>
        value !== undefined &&
        value !== null &&
        value !== ""
    )
  ) as Record<
    string,
    string | number | boolean
  >;
}

export const warehouseLocationService = {
  async getLocations(
    query: WarehouseLocationQuery = {}
  ): Promise<
    WarehouseLocationListResponse["data"]
  > {
    const response =
      await api.get<WarehouseLocationListResponse>(
        "/api/warehouse-locations",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getLocation(
    locationId: number
  ): Promise<WarehouseLocation> {
    const response =
      await api.get<WarehouseLocationResponse>(
        `/api/warehouse-locations/${locationId}`
      );

    return response.data.data.location;
  },

  async createLocation(
    payload: WarehouseLocationPayload
  ): Promise<WarehouseLocation> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.post<WarehouseLocationResponse>(
        "/api/warehouse-locations",
        payload
      );

    return response.data.data.location;
  },

  async updateLocation(
    locationId: number,
    payload: Partial<WarehouseLocationPayload>
  ): Promise<WarehouseLocation> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.patch<WarehouseLocationResponse>(
        `/api/warehouse-locations/${locationId}`,
        payload
      );

    return response.data.data.location;
  },

  async deleteLocation(
    locationId: number
  ): Promise<void> {
    await api.get("/sanctum/csrf-cookie");

    await api.delete(
      `/api/warehouse-locations/${locationId}`
    );
  },

  async restoreLocation(
    locationId: number
  ): Promise<WarehouseLocation> {
    await api.get("/sanctum/csrf-cookie");

    const response =
      await api.post<WarehouseLocationResponse>(
        `/api/warehouse-locations/${locationId}/restore`
      );

    return response.data.data.location;
  },
};