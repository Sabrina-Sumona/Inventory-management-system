"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";

import { WarehouseLocationForm } from "@/components/warehouse-locations/WarehouseLocationForm";
import { useAuth } from "@/contexts/AuthContext";
import { warehouseLocationService } from "@/services/warehouseLocationService";
import { warehouseService } from "@/services/warehouseService";

import type { ApiErrorResponse } from "@/types/auth";
import type { Warehouse } from "@/types/warehouse";
import type {
  WarehouseLocation,
  WarehouseLocationPagination,
  WarehouseLocationPayload,
  WarehouseLocationQuery,
  WarehouseLocationType,
} from "@/types/warehouseLocation";

const emptyPagination: WarehouseLocationPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
};

const inputClassName =
  "h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500";

const selectClassName =
  "h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500";

function getApiMessage(
  error: unknown,
  fallback: string
): string {
  const axiosError =
    error as AxiosError<ApiErrorResponse>;

  return (
    axiosError.response?.data?.message ??
    fallback
  );
}

function getValidationErrors(
  error: unknown
): Record<string, string> {
  const axiosError =
    error as AxiosError<ApiErrorResponse>;

  const errors =
    axiosError.response?.data?.errors ?? {};

  return Object.fromEntries(
    Object.entries(errors).map(
      ([field, messages]) => [
        field,
        messages[0] ??
          "The provided value is invalid.",
      ]
    )
  );
}

function formatLocationType(
  type: WarehouseLocationType
): string {
  return (
    type.charAt(0).toUpperCase() +
    type.slice(1)
  );
}

function getTypeBadgeClass(
  type: WarehouseLocationType
): string {
  switch (type) {
    case "zone":
      return "bg-blue-100 text-blue-800";

    case "rack":
      return "bg-violet-100 text-violet-800";

    case "shelf":
      return "bg-amber-100 text-amber-800";

    case "bin":
      return "bg-cyan-100 text-cyan-800";

    default:
      return "bg-slate-100 text-slate-700";
  }
}

export default function WarehouseLocationsPage() {
  const { user } = useAuth();

  const [locations, setLocations] =
    useState<WarehouseLocation[]>([]);

  const [
    allAccessibleLocations,
    setAllAccessibleLocations,
  ] = useState<WarehouseLocation[]>([]);

  const [warehouses, setWarehouses] =
    useState<Warehouse[]>([]);

  const [pagination, setPagination] =
    useState<WarehouseLocationPagination>(
      emptyPagination
    );

  const [search, setSearch] = useState("");

  const [
    warehouseFilter,
    setWarehouseFilter,
  ] = useState("");

  const [typeFilter, setTypeFilter] =
    useState<"all" | WarehouseLocationType>(
      "all"
    );

  const [activeFilter, setActiveFilter] =
    useState<
      "all" | "active" | "inactive"
    >("all");

  const [
    editingLocation,
    setEditingLocation,
  ] = useState<WarehouseLocation | null>(
    null
  );

  const [isFormOpen, setIsFormOpen] =
    useState(false);

  const [isLoading, setIsLoading] =
    useState(true);

  const [
    isLoadingWarehouses,
    setIsLoadingWarehouses,
  ] = useState(true);

  const [isSaving, setIsSaving] =
    useState(false);

  const [updatingId, setUpdatingId] =
    useState<number | null>(null);

  const [deletingId, setDeletingId] =
    useState<number | null>(null);

  const [formErrors, setFormErrors] =
    useState<Record<string, string>>({});

  const [generalError, setGeneralError] =
    useState<string | null>(null);

  const [successMessage, setSuccessMessage] =
    useState<string | null>(null);

  const canCreate =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "warehouse-location.create"
    ) ?? false;

  const canUpdate =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "warehouse-location.update"
    ) ?? false;

  const canDelete =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "warehouse-location.delete"
    ) ?? false;

  async function loadLocations(
    page = 1
  ): Promise<void> {
    setIsLoading(true);
    setGeneralError(null);

    const query: WarehouseLocationQuery = {
      search: search.trim() || undefined,

      warehouse_id:
        warehouseFilter === ""
          ? undefined
          : Number(warehouseFilter),

      type:
        typeFilter === "all"
          ? undefined
          : typeFilter,

      is_active:
        activeFilter === "all"
          ? undefined
          : activeFilter === "active",

      sort_by: "name",
      sort_direction: "asc",
      page,
      per_page: 10,
    };

    try {
      const data =
        await warehouseLocationService.getLocations(
          query
        );

      setLocations(data.locations);
      setPagination(data.pagination);
    } catch (error) {
      setGeneralError(
        getApiMessage(
          error,
          "Unable to load warehouse locations."
        )
      );
    } finally {
      setIsLoading(false);
    }
  }

  async function loadFormLocations(): Promise<void> {
    try {
      const data =
        await warehouseLocationService.getLocations({
          page: 1,
          per_page: 100,
          sort_by: "name",
          sort_direction: "asc",
        });

      setAllAccessibleLocations(
        data.locations
      );
    } catch (error) {
      setGeneralError(
        getApiMessage(
          error,
          "Unable to load warehouse-location hierarchy."
        )
      );
    }
  }

  async function refreshPageData(
    page = pagination.current_page
  ): Promise<void> {
    await Promise.all([
      loadLocations(page),
      loadFormLocations(),
    ]);
  }

  useEffect(() => {
    let isMounted = true;

    warehouseService
      .getWarehouses({
        page: 1,
        per_page: 100,
        sort_by: "name",
        sort_direction: "asc",
      })
      .then((data) => {
        if (!isMounted) {
          return;
        }

        setWarehouses(
          data.warehouses.filter(
            (warehouse) =>
              warehouse.is_active
          )
        );
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return;
        }

        setGeneralError(
          getApiMessage(
            error,
            "Unable to load accessible warehouses."
          )
        );
      })
      .finally(() => {
        if (isMounted) {
          setIsLoadingWarehouses(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, []);

  useEffect(() => {
    let isMounted = true;

    Promise.all([
      warehouseLocationService.getLocations({
        page: 1,
        per_page: 10,
        sort_by: "name",
        sort_direction: "asc",
      }),

      warehouseLocationService.getLocations({
        page: 1,
        per_page: 100,
        sort_by: "name",
        sort_direction: "asc",
      }),
    ])
      .then(
        ([
          paginatedData,
          completeData,
        ]) => {
          if (!isMounted) {
            return;
          }

          setLocations(
            paginatedData.locations
          );

          setPagination(
            paginatedData.pagination
          );

          setAllAccessibleLocations(
            completeData.locations
          );
        }
      )
      .catch((error: unknown) => {
        if (!isMounted) {
          return;
        }

        setGeneralError(
          getApiMessage(
            error,
            "Unable to load warehouse locations."
          )
        );
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, []);

  async function handleSearch(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    setSuccessMessage(null);

    await loadLocations(1);
  }

  async function handleResetFilters(): Promise<void> {
    setSearch("");
    setWarehouseFilter("");
    setTypeFilter("all");
    setActiveFilter("all");
    setSuccessMessage(null);
    setGeneralError(null);
    setIsLoading(true);

    try {
      const data =
        await warehouseLocationService.getLocations({
          page: 1,
          per_page: 10,
          sort_by: "name",
          sort_direction: "asc",
        });

      setLocations(data.locations);
      setPagination(data.pagination);
    } catch (error) {
      setGeneralError(
        getApiMessage(
          error,
          "Unable to reset warehouse-location filters."
        )
      );
    } finally {
      setIsLoading(false);
    }
  }

  function openCreateForm(): void {
    setEditingLocation(null);
    setFormErrors({});
    setGeneralError(null);
    setSuccessMessage(null);
    setIsFormOpen(true);
  }

  function openEditForm(
    location: WarehouseLocation
  ): void {
    setEditingLocation(location);
    setFormErrors({});
    setGeneralError(null);
    setSuccessMessage(null);
    setIsFormOpen(true);
  }

  function closeForm(): void {
    if (isSaving) {
      return;
    }

    setIsFormOpen(false);
    setEditingLocation(null);
    setFormErrors({});
  }

  async function handleSave(
    payload: WarehouseLocationPayload
  ): Promise<void> {
    setIsSaving(true);
    setFormErrors({});
    setGeneralError(null);
    setSuccessMessage(null);

    try {
      if (editingLocation) {
        await warehouseLocationService.updateLocation(
          editingLocation.id,
          payload
        );

        setSuccessMessage(
          "Warehouse location updated successfully."
        );
      } else {
        await warehouseLocationService.createLocation(
          payload
        );

        setSuccessMessage(
          "Warehouse location created successfully."
        );
      }

      setIsFormOpen(false);
      setEditingLocation(null);

      await refreshPageData(
        pagination.current_page
      );
    } catch (error) {
      setFormErrors(
        getValidationErrors(error)
      );

      setGeneralError(
        getApiMessage(
          error,
          "Unable to save the warehouse location."
        )
      );
    } finally {
      setIsSaving(false);
    }
  }

  async function handleStatusChange(
    location: WarehouseLocation
  ): Promise<void> {
    setUpdatingId(location.id);
    setGeneralError(null);
    setSuccessMessage(null);

    try {
      await warehouseLocationService.updateLocation(
        location.id,
        {
          is_active:
            !location.is_active,
        }
      );

      setSuccessMessage(
        location.is_active
          ? "Warehouse location deactivated successfully."
          : "Warehouse location activated successfully."
      );

      await refreshPageData(
        pagination.current_page
      );
    } catch (error) {
      setGeneralError(
        getApiMessage(
          error,
          "Unable to update warehouse-location status."
        )
      );
    } finally {
      setUpdatingId(null);
    }
  }

  async function handleDelete(
    location: WarehouseLocation
  ): Promise<void> {
    if (location.children_count > 0) {
      setGeneralError(
        "A warehouse location containing child locations cannot be deleted."
      );

      return;
    }

    const confirmed = window.confirm(
      `Delete "${location.name}"? This action will soft-delete the warehouse location.`
    );

    if (!confirmed) {
      return;
    }

    setDeletingId(location.id);
    setGeneralError(null);
    setSuccessMessage(null);

    try {
      await warehouseLocationService.deleteLocation(
        location.id
      );

      setSuccessMessage(
        "Warehouse location deleted successfully."
      );

      const nextPage =
        locations.length === 1 &&
        pagination.current_page > 1
          ? pagination.current_page - 1
          : pagination.current_page;

      await refreshPageData(nextPage);
    } catch (error) {
      setGeneralError(
        getApiMessage(
          error,
          "Unable to delete the warehouse location."
        )
      );
    } finally {
      setDeletingId(null);
    }
  }

  return (
    <div className="space-y-6">
      <section className="flex flex-col justify-between gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-950">
            Warehouse Locations
          </h2>

          <p className="mt-2 text-sm text-slate-500">
            Manage zones, racks, shelves and
            bins inside accessible warehouses.
          </p>
        </div>

        {canCreate && (
          <button
            type="button"
            onClick={openCreateForm}
            disabled={
              isLoadingWarehouses ||
              warehouses.length === 0
            }
            className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
          >
            Add location
          </button>
        )}
      </section>

      {successMessage && (
        <div
          role="status"
          className="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800"
        >
          {successMessage}
        </div>
      )}

      {generalError && (
        <div
          role="alert"
          className="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
        >
          {generalError}
        </div>
      )}

      <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form
          onSubmit={handleSearch}
          className="grid gap-4 border-b border-slate-200 p-5 lg:grid-cols-[1.4fr_1fr_170px_170px_auto_auto]"
        >
          <input
            type="search"
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
            placeholder="Search name, code, barcode or description"
            className={inputClassName}
          />

          <select
            value={warehouseFilter}
            onChange={(event) =>
              setWarehouseFilter(
                event.target.value
              )
            }
            disabled={isLoadingWarehouses}
            className={selectClassName}
          >
            <option value="">
              All warehouses
            </option>

            {warehouses.map((warehouse) => (
              <option
                key={warehouse.id}
                value={warehouse.id}
              >
                {warehouse.name} (
                {warehouse.code})
              </option>
            ))}
          </select>

          <select
            value={typeFilter}
            onChange={(event) =>
              setTypeFilter(
                event.target.value as
                  | "all"
                  | WarehouseLocationType
              )
            }
            className={selectClassName}
          >
            <option value="all">
              All types
            </option>

            <option value="zone">
              Zone
            </option>

            <option value="rack">
              Rack
            </option>

            <option value="shelf">
              Shelf
            </option>

            <option value="bin">
              Bin
            </option>
          </select>

          <select
            value={activeFilter}
            onChange={(event) =>
              setActiveFilter(
                event.target.value as
                  | "all"
                  | "active"
                  | "inactive"
              )
            }
            className={selectClassName}
          >
            <option value="all">
              All statuses
            </option>

            <option value="active">
              Active
            </option>

            <option value="inactive">
              Inactive
            </option>
          </select>

          <button
            type="submit"
            disabled={isLoading}
            className="h-11 rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-500"
          >
            {isLoading
              ? "Loading..."
              : "Search"}
          </button>

          <button
            type="button"
            onClick={() =>
              void handleResetFilters()
            }
            disabled={isLoading}
            className="h-11 rounded-lg border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Reset
          </button>
        </form>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                {[
                  "Location",
                  "Warehouse",
                  "Hierarchy",
                  "Barcode",
                  "Capacity",
                  "Status",
                  "Actions",
                ].map((heading) => (
                  <th
                    key={heading}
                    className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                  >
                    {heading}
                  </th>
                ))}
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-200 bg-white">
              {isLoading ? (
                <tr>
                  <td
                    colSpan={7}
                    className="px-5 py-12 text-center text-sm text-slate-500"
                  >
                    Loading warehouse
                    locations...
                  </td>
                </tr>
              ) : locations.length === 0 ? (
                <tr>
                  <td
                    colSpan={7}
                    className="px-5 py-12 text-center text-sm text-slate-500"
                  >
                    No warehouse locations
                    found.
                  </td>
                </tr>
              ) : (
                locations.map((location) => (
                  <tr
                    key={location.id}
                    className="align-top"
                  >
                    <td className="px-5 py-4">
                      <p className="font-semibold text-slate-900">
                        {location.name}
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        {location.code}
                      </p>

                      <span
                        className={`mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${getTypeBadgeClass(
                          location.type
                        )}`}
                      >
                        {formatLocationType(
                          location.type
                        )}
                      </span>
                    </td>

                    <td className="px-5 py-4">
                      <p className="text-sm font-medium text-slate-800">
                        {location.warehouse.name}
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        {location.warehouse.code}
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        {location.branch.name}
                      </p>
                    </td>

                    <td className="px-5 py-4">
                      {location.parent ? (
                        <>
                          <p className="text-sm text-slate-700">
                            {location.parent.name}
                          </p>

                          <p className="mt-1 text-xs text-slate-500">
                            Parent:{" "}
                            {location.parent.code}
                          </p>
                        </>
                      ) : (
                        <p className="text-sm text-slate-500">
                          Root location
                        </p>
                      )}

                      <p className="mt-2 text-xs text-slate-500">
                        Children:{" "}
                        {location.children_count}
                      </p>
                    </td>

                    <td className="px-5 py-4 text-sm text-slate-600">
                      {location.barcode ?? "—"}
                    </td>

                    <td className="px-5 py-4 text-sm text-slate-600">
                      {location.capacity !== null
                        ? location.capacity
                        : "—"}
                    </td>

                    <td className="px-5 py-4">
                      <span
                        className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                          location.is_active
                            ? "bg-emerald-100 text-emerald-800"
                            : "bg-red-100 text-red-800"
                        }`}
                      >
                        {location.is_active
                          ? "Active"
                          : "Inactive"}
                      </span>
                    </td>

                    <td className="px-5 py-4">
                      <div className="flex flex-wrap gap-2">
                        {canUpdate && (
                          <>
                            <button
                              type="button"
                              onClick={() =>
                                openEditForm(
                                  location
                                )
                              }
                              disabled={
                                updatingId ===
                                  location.id ||
                                deletingId ===
                                  location.id
                              }
                              className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                              Edit
                            </button>

                            <button
                              type="button"
                              onClick={() =>
                                void handleStatusChange(
                                  location
                                )
                              }
                              disabled={
                                updatingId ===
                                  location.id ||
                                deletingId ===
                                  location.id
                              }
                              className="rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 transition hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                              {updatingId ===
                              location.id
                                ? "Updating..."
                                : location.is_active
                                  ? "Deactivate"
                                  : "Activate"}
                            </button>
                          </>
                        )}

                        {canDelete && (
                          <button
                            type="button"
                            onClick={() =>
                              void handleDelete(
                                location
                              )
                            }
                            disabled={
                              location.children_count >
                                0 ||
                              deletingId ===
                                location.id ||
                              updatingId ===
                                location.id
                            }
                            title={
                              location.children_count >
                              0
                                ? "Locations containing children cannot be deleted."
                                : undefined
                            }
                            className="rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400"
                          >
                            {deletingId ===
                            location.id
                              ? "Deleting..."
                              : "Delete"}
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="flex flex-col justify-between gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
          <p className="text-sm text-slate-500">
            Showing {pagination.from ?? 0}–
            {pagination.to ?? 0} of{" "}
            {pagination.total} locations
          </p>

          <div className="flex items-center gap-2">
            <button
              type="button"
              disabled={
                isLoading ||
                pagination.current_page <= 1
              }
              onClick={() =>
                void loadLocations(
                  pagination.current_page - 1
                )
              }
              className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Previous
            </button>

            <span className="px-3 text-sm text-slate-600">
              Page{" "}
              {pagination.current_page} of{" "}
              {pagination.last_page}
            </span>

            <button
              type="button"
              disabled={
                isLoading ||
                pagination.current_page >=
                  pagination.last_page
              }
              onClick={() =>
                void loadLocations(
                  pagination.current_page + 1
                )
              }
              className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </section>

      {isFormOpen && (
        <WarehouseLocationForm
          key={
            editingLocation?.id ??
            "new-warehouse-location"
          }
          location={editingLocation}
          warehouses={warehouses}
          availableLocations={
            allAccessibleLocations
          }
          isSaving={isSaving}
          errors={formErrors}
          onCancel={closeForm}
          onSubmit={handleSave}
        />
      )}
    </div>
  );
}