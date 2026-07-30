"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";

import { WarehouseForm } from "@/components/warehouses/WarehouseForm";
import { useAuth } from "@/contexts/AuthContext";
import { branchService } from "@/services/branchService";
import { warehouseService } from "@/services/warehouseService";
import type { ApiErrorResponse } from "@/types/auth";
import type { Branch } from "@/types/branch";
import type {
  Warehouse,
  WarehousePagination,
  WarehousePayload,
  WarehouseQuery,
} from "@/types/warehouse";

const emptyPagination: WarehousePagination = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
};

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

export default function WarehousesPage() {
  const { user } = useAuth();

  const [warehouses, setWarehouses] =
    useState<Warehouse[]>([]);

  const [branches, setBranches] =
    useState<Branch[]>([]);

  const [pagination, setPagination] =
    useState<WarehousePagination>(
      emptyPagination
    );

  const [search, setSearch] = useState("");

  const [activeFilter, setActiveFilter] =
    useState<
      "all" | "active" | "inactive"
    >("all");

  const [primaryFilter, setPrimaryFilter] =
    useState<
      "all" | "primary" | "regular"
    >("all");

  const [
    editingWarehouse,
    setEditingWarehouse,
  ] = useState<Warehouse | null>(null);

  const [isFormOpen, setIsFormOpen] =
    useState(false);

  const [isLoading, setIsLoading] =
    useState(true);

  const [isSaving, setIsSaving] =
    useState(false);

  const [
    updatingWarehouseId,
    setUpdatingWarehouseId,
  ] = useState<number | null>(null);

  const [
    deletingWarehouseId,
    setDeletingWarehouseId,
  ] = useState<number | null>(null);

  const [formErrors, setFormErrors] =
    useState<Record<string, string>>({});

  const [
    errorMessage,
    setErrorMessage,
  ] = useState<string | null>(null);

  const [
    successMessage,
    setSuccessMessage,
  ] = useState<string | null>(null);

  const canCreate =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "warehouse.create"
    ) ?? false;

  const canUpdate =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "warehouse.update"
    ) ?? false;

  const canDelete =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "warehouse.delete"
    ) ?? false;

  async function loadWarehouses(
    page = 1
  ): Promise<void> {
    setIsLoading(true);
    setErrorMessage(null);

    const query: WarehouseQuery = {
      search:
        search.trim() || undefined,

      is_active:
        activeFilter === "all"
          ? undefined
          : activeFilter === "active",

      is_primary:
        primaryFilter === "all"
          ? undefined
          : primaryFilter === "primary",

      page,
      per_page: 10,
      sort_by: "name",
      sort_direction: "asc",
    };

    try {
      const data =
        await warehouseService.getWarehouses(
          query
        );

      setWarehouses(data.warehouses);
      setPagination(data.pagination);
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load warehouses."
        )
      );
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => {
    let isMounted = true;

    warehouseService
      .getWarehouses({
        page: 1,
        per_page: 10,
        sort_by: "name",
        sort_direction: "asc",
      })
      .then((data) => {
        if (!isMounted) {
          return;
        }

        setWarehouses(data.warehouses);
        setPagination(data.pagination);
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return;
        }

        setErrorMessage(
          getApiMessage(
            error,
            "Unable to load warehouses."
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

  useEffect(() => {
    let isMounted = true;

    branchService
      .getBranches({
        sort_by: "name",
        sort_direction: "asc",
        per_page: 100,
        page: 1,
      })
      .then((data) => {
        if (!isMounted) {
          return;
        }

        const activeBranches =
          data.branches.filter(
            (branch) => branch.is_active
          );

        setBranches(activeBranches);
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return;
        }

        setErrorMessage(
          getApiMessage(
            error,
            "Unable to load accessible branches."
          )
        );
      });

    return () => {
      isMounted = false;
    };
  }, []);

  async function handleSearch(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    await loadWarehouses(1);
  }

  function openCreateForm(): void {
    setEditingWarehouse(null);
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsFormOpen(true);
  }

  function openEditForm(
    warehouse: Warehouse
  ): void {
    setEditingWarehouse(warehouse);
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsFormOpen(true);
  }

  function closeForm(): void {
    if (isSaving) {
      return;
    }

    setIsFormOpen(false);
    setEditingWarehouse(null);
    setFormErrors({});
  }

  async function handleSave(
    payload: WarehousePayload
  ): Promise<void> {
    setIsSaving(true);
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      if (editingWarehouse) {
        await warehouseService.updateWarehouse(
          editingWarehouse.id,
          payload
        );

        setSuccessMessage(
          "Warehouse updated successfully."
        );
      } else {
        await warehouseService.createWarehouse(
          payload
        );

        setSuccessMessage(
          "Warehouse created successfully."
        );
      }

      setIsFormOpen(false);
      setEditingWarehouse(null);

      await loadWarehouses(
        pagination.current_page
      );
    } catch (error) {
      setFormErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to save the warehouse."
        )
      );
    } finally {
      setIsSaving(false);
    }
  }

  async function handleStatusChange(
    warehouse: Warehouse
  ): Promise<void> {
    setUpdatingWarehouseId(warehouse.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await warehouseService.updateWarehouse(
        warehouse.id,
        {
          is_active: !warehouse.is_active,
        }
      );

      setSuccessMessage(
        warehouse.is_active
          ? "Warehouse deactivated successfully."
          : "Warehouse activated successfully."
      );

      await loadWarehouses(
        pagination.current_page
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to update warehouse status."
        )
      );
    } finally {
      setUpdatingWarehouseId(null);
    }
  }

  async function handleDelete(
    warehouse: Warehouse
  ): Promise<void> {
    const confirmed = window.confirm(
      `Delete "${warehouse.name}"? This will soft-delete the warehouse.`
    );

    if (!confirmed) {
      return;
    }

    setDeletingWarehouseId(warehouse.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await warehouseService.deleteWarehouse(
        warehouse.id
      );

      setSuccessMessage(
        "Warehouse deleted successfully."
      );

      const nextPage =
        warehouses.length === 1 &&
        pagination.current_page > 1
          ? pagination.current_page - 1
          : pagination.current_page;

      await loadWarehouses(nextPage);
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to delete the warehouse."
        )
      );
    } finally {
      setDeletingWarehouseId(null);
    }
  }

  return (
    <div className="space-y-6">
      <section className="flex flex-col justify-between gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-950">
            Warehouse Management
          </h2>

          <p className="mt-2 text-sm text-slate-500">
            Manage company warehouses,
            branches, operational status,
            and access.
          </p>
        </div>

        {canCreate && (
          <button
            type="button"
            onClick={openCreateForm}
            className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
          >
            Add warehouse
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

      {errorMessage && (
        <div
          role="alert"
          className="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
        >
          {errorMessage}
        </div>
      )}

      <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form
          onSubmit={handleSearch}
          className="grid gap-4 border-b border-slate-200 p-5 lg:grid-cols-[1fr_190px_190px_auto]"
        >
          <input
            type="search"
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
            placeholder="Search warehouse name, code, city or district"
            className="h-11 rounded-lg border border-slate-300 px-4 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
          />

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
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 outline-none focus:border-emerald-600"
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

          <select
            value={primaryFilter}
            onChange={(event) =>
              setPrimaryFilter(
                event.target.value as
                  | "all"
                  | "primary"
                  | "regular"
              )
            }
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 outline-none focus:border-emerald-600"
          >
            <option value="all">
              All warehouse types
            </option>
            <option value="primary">
              Primary warehouse
            </option>
            <option value="regular">
              Regular warehouse
            </option>
          </select>

          <button
            type="submit"
            disabled={isLoading}
            className="h-11 rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-500"
          >
            Search
          </button>
        </form>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                {[
                  "Warehouse",
                  "Branch",
                  "Location",
                  "Contact",
                  "Users",
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
                    Loading warehouses...
                  </td>
                </tr>
              ) : warehouses.length === 0 ? (
                <tr>
                  <td
                    colSpan={7}
                    className="px-5 py-12 text-center text-sm text-slate-500"
                  >
                    No warehouses found.
                  </td>
                </tr>
              ) : (
                warehouses.map(
                  (warehouse) => (
                    <tr
                      key={warehouse.id}
                      className="align-top"
                    >
                      <td className="px-5 py-4">
                        <p className="font-semibold text-slate-900">
                          {warehouse.name}
                        </p>

                        <p className="mt-1 text-xs text-slate-500">
                          {warehouse.code}
                        </p>

                        {warehouse.is_primary && (
                          <span className="mt-2 inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                            Primary
                          </span>
                        )}
                      </td>

                      <td className="px-5 py-4">
                        <p className="text-sm font-medium text-slate-700">
                          {warehouse.branch.name}
                        </p>

                        <p className="mt-1 text-xs text-slate-500">
                          {warehouse.branch.code}
                        </p>

                        {warehouse.branch
                          .is_head_office && (
                          <span className="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                            Head office
                          </span>
                        )}
                      </td>

                      <td className="px-5 py-4 text-sm text-slate-600">
                        <p>
                          {warehouse.city ??
                            "No city"}
                        </p>

                        <p className="mt-1 text-xs text-slate-500">
                          {warehouse.district ??
                            "No district"}
                        </p>
                      </td>

                      <td className="px-5 py-4 text-sm text-slate-600">
                        <p>
                          {warehouse.email ?? "—"}
                        </p>

                        <p className="mt-1">
                          {warehouse.phone ?? "—"}
                        </p>
                      </td>

                      <td className="px-5 py-4 text-sm font-semibold text-slate-700">
                        {warehouse.users_count}
                      </td>

                      <td className="px-5 py-4">
                        <span
                          className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                            warehouse.is_active
                              ? "bg-emerald-100 text-emerald-800"
                              : "bg-red-100 text-red-800"
                          }`}
                        >
                          {warehouse.is_active
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
                                    warehouse
                                  )
                                }
                                disabled={
                                  updatingWarehouseId ===
                                    warehouse.id ||
                                  deletingWarehouseId ===
                                    warehouse.id
                                }
                                className="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                              >
                                Edit
                              </button>

                              <button
                                type="button"
                                onClick={() =>
                                  void handleStatusChange(
                                    warehouse
                                  )
                                }
                                disabled={
                                  warehouse.is_primary ||
                                  updatingWarehouseId ===
                                    warehouse.id ||
                                  deletingWarehouseId ===
                                    warehouse.id
                                }
                                title={
                                  warehouse.is_primary
                                    ? "A primary warehouse must remain active."
                                    : undefined
                                }
                                className="rounded-md border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50"
                              >
                                {updatingWarehouseId ===
                                warehouse.id
                                  ? "Updating..."
                                  : warehouse.is_active
                                    ? "Deactivate"
                                    : "Activate"}
                              </button>
                            </>
                          )}

                          {canDelete &&
                            !warehouse.is_primary && (
                              <button
                                type="button"
                                onClick={() =>
                                  void handleDelete(
                                    warehouse
                                  )
                                }
                                disabled={
                                  deletingWarehouseId ===
                                    warehouse.id ||
                                  updatingWarehouseId ===
                                    warehouse.id
                                }
                                className="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                              >
                                {deletingWarehouseId ===
                                warehouse.id
                                  ? "Deleting..."
                                  : "Delete"}
                              </button>
                            )}
                        </div>
                      </td>
                    </tr>
                  )
                )
              )}
            </tbody>
          </table>
        </div>

        <div className="flex flex-col justify-between gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
          <p className="text-sm text-slate-500">
            Showing {pagination.from ?? 0}–
            {pagination.to ?? 0} of{" "}
            {pagination.total} warehouses
          </p>

          <div className="flex gap-2">
            <button
              type="button"
              disabled={
                isLoading ||
                pagination.current_page <= 1
              }
              onClick={() =>
                void loadWarehouses(
                  pagination.current_page - 1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Previous
            </button>

            <span className="flex items-center px-3 text-sm text-slate-600">
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
                void loadWarehouses(
                  pagination.current_page + 1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </section>

      {isFormOpen && (
        <WarehouseForm
          key={
            editingWarehouse?.id ??
            "new-warehouse"
          }
          warehouse={editingWarehouse}
          branches={branches}
          isSaving={isSaving}
          errors={formErrors}
          onCancel={closeForm}
          onSubmit={handleSave}
        />
      )}
    </div>
  );
}