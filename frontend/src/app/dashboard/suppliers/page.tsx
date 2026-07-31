"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";

import { SupplierForm } from "@/components/suppliers/SupplierForm";
import { useAuth } from "@/contexts/AuthContext";
import { branchService } from "@/services/branchService";
import { supplierService } from "@/services/supplierService";
import type { ApiErrorResponse } from "@/types/auth";
import type { Branch } from "@/types/branch";
import type {
  Supplier,
  SupplierOpeningBalanceType,
  SupplierPagination,
  SupplierPayload,
  SupplierQuery,
} from "@/types/supplier";

const emptyPagination: SupplierPagination = {
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

function formatMoney(
  value: string | number
): string {
  const numericValue = Number(value);

  if (!Number.isFinite(numericValue)) {
    return "৳0.00";
  }

  return new Intl.NumberFormat("en-BD", {
    style: "currency",
    currency: "BDT",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numericValue);
}

export default function SuppliersPage() {
  const { user } = useAuth();

  const [suppliers, setSuppliers] =
    useState<Supplier[]>([]);

  const [branches, setBranches] =
    useState<Branch[]>([]);

  const [pagination, setPagination] =
    useState<SupplierPagination>(
      emptyPagination
    );

  const [search, setSearch] = useState("");

  const [activeFilter, setActiveFilter] =
    useState<
      "all" | "active" | "inactive"
    >("all");

  const [
    balanceTypeFilter,
    setBalanceTypeFilter,
  ] = useState<
    "all" | SupplierOpeningBalanceType
  >("all");

  const [branchFilter, setBranchFilter] =
    useState<string>("all");

  const [
    editingSupplier,
    setEditingSupplier,
  ] = useState<Supplier | null>(null);

  const [isFormOpen, setIsFormOpen] =
    useState(false);

  const [isLoading, setIsLoading] =
    useState(true);

  const [isSaving, setIsSaving] =
    useState(false);

  const [
    updatingSupplierId,
    setUpdatingSupplierId,
  ] = useState<number | null>(null);

  const [
    deletingSupplierId,
    setDeletingSupplierId,
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
        "supplier.create"
    ) ?? false;

  const canUpdate =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "supplier.update"
    ) ?? false;

  const canDelete =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "supplier.delete"
    ) ?? false;

  async function loadSuppliers(
    page = 1
  ): Promise<void> {
    setIsLoading(true);
    setErrorMessage(null);

    const selectedBranchId =
      branchFilter === "all"
        ? undefined
        : Number(branchFilter);

    const query: SupplierQuery = {
      search:
        search.trim() || undefined,

      branch_id:
        selectedBranchId &&
        Number.isInteger(selectedBranchId)
          ? selectedBranchId
          : undefined,

      is_active:
        activeFilter === "all"
          ? undefined
          : activeFilter === "active",

      opening_balance_type:
        balanceTypeFilter === "all"
          ? undefined
          : balanceTypeFilter,

      page,
      per_page: 10,
      sort_by: "name",
      sort_direction: "asc",
    };

    try {
      const data =
        await supplierService.getSuppliers(
          query
        );

      setSuppliers(data.suppliers);
      setPagination(data.pagination);
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load suppliers."
        )
      );
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => {
    let isMounted = true;

    supplierService
      .getSuppliers({
        page: 1,
        per_page: 10,
        sort_by: "name",
        sort_direction: "asc",
      })
      .then((data) => {
        if (!isMounted) {
          return;
        }

        setSuppliers(data.suppliers);
        setPagination(data.pagination);
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return;
        }

        setErrorMessage(
          getApiMessage(
            error,
            "Unable to load suppliers."
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

    await loadSuppliers(1);
  }

  function openCreateForm(): void {
    setEditingSupplier(null);
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsFormOpen(true);
  }

  function openEditForm(
    supplier: Supplier
  ): void {
    setEditingSupplier(supplier);
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
    setEditingSupplier(null);
    setFormErrors({});
  }

  async function handleSave(
    payload: SupplierPayload
  ): Promise<void> {
    setIsSaving(true);
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      if (editingSupplier) {
        await supplierService.updateSupplier(
          editingSupplier.id,
          payload
        );

        setSuccessMessage(
          "Supplier updated successfully."
        );
      } else {
        await supplierService.createSupplier(
          payload
        );

        setSuccessMessage(
          "Supplier created successfully."
        );
      }

      setIsFormOpen(false);
      setEditingSupplier(null);

      await loadSuppliers(
        pagination.current_page
      );
    } catch (error) {
      setFormErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to save the supplier."
        )
      );
    } finally {
      setIsSaving(false);
    }
  }

  async function handleStatusChange(
    supplier: Supplier
  ): Promise<void> {
    setUpdatingSupplierId(supplier.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await supplierService.updateSupplier(
        supplier.id,
        {
          is_active: !supplier.is_active,
        }
      );

      setSuccessMessage(
        supplier.is_active
          ? "Supplier deactivated successfully."
          : "Supplier activated successfully."
      );

      await loadSuppliers(
        pagination.current_page
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to update supplier status."
        )
      );
    } finally {
      setUpdatingSupplierId(null);
    }
  }

  async function handleDelete(
    supplier: Supplier
  ): Promise<void> {
    const confirmed = window.confirm(
      `Delete "${supplier.name}"? This will soft-delete the supplier.`
    );

    if (!confirmed) {
      return;
    }

    setDeletingSupplierId(supplier.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await supplierService.deleteSupplier(
        supplier.id
      );

      setSuccessMessage(
        "Supplier deleted successfully."
      );

      const nextPage =
        suppliers.length === 1 &&
        pagination.current_page > 1
          ? pagination.current_page - 1
          : pagination.current_page;

      await loadSuppliers(nextPage);
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to delete the supplier."
        )
      );
    } finally {
      setDeletingSupplierId(null);
    }
  }

  return (
    <div className="space-y-6">
      <section className="flex flex-col justify-between gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-950">
            Supplier Management
          </h2>

          <p className="mt-2 text-sm text-slate-500">
            Manage supplier contact details,
            branch access, payment terms,
            balances, and operational status.
          </p>
        </div>

        {canCreate && (
          <button
            type="button"
            onClick={openCreateForm}
            className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
          >
            Add supplier
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
          className="grid gap-4 border-b border-slate-200 p-5 xl:grid-cols-[1fr_180px_180px_190px_auto]"
        >
          <input
            type="search"
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
            placeholder="Search supplier name, code, email or phone"
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
            value={balanceTypeFilter}
            onChange={(event) =>
              setBalanceTypeFilter(
                event.target.value as
                  | "all"
                  | SupplierOpeningBalanceType
              )
            }
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 outline-none focus:border-emerald-600"
          >
            <option value="all">
              All balance types
            </option>

            <option value="payable">
              Payable
            </option>

            <option value="receivable">
              Receivable
            </option>
          </select>

          <select
            value={branchFilter}
            onChange={(event) =>
              setBranchFilter(
                event.target.value
              )
            }
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 outline-none focus:border-emerald-600"
          >
            <option value="all">
              All branches
            </option>

            {branches.map((branch) => (
              <option
                key={branch.id}
                value={branch.id}
              >
                {branch.name}
              </option>
            ))}
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
                  "Supplier",
                  "Branch",
                  "Contact",
                  "Payment Terms",
                  "Opening Balance",
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
                    Loading suppliers...
                  </td>
                </tr>
              ) : suppliers.length === 0 ? (
                <tr>
                  <td
                    colSpan={7}
                    className="px-5 py-12 text-center text-sm text-slate-500"
                  >
                    No suppliers found.
                  </td>
                </tr>
              ) : (
                suppliers.map((supplier) => (
                  <tr
                    key={supplier.id}
                    className="align-top"
                  >
                    <td className="px-5 py-4">
                      <p className="font-semibold text-slate-900">
                        {supplier.name}
                      </p>

                      <p className="mt-1 text-xs font-medium text-slate-500">
                        {supplier.code}
                      </p>

                      {supplier.business_name && (
                        <p className="mt-2 max-w-xs text-xs text-slate-500">
                          {supplier.business_name}
                        </p>
                      )}
                    </td>

                    <td className="px-5 py-4">
                      {supplier.branch ? (
                        <>
                          <p className="text-sm font-medium text-slate-700">
                            {supplier.branch.name}
                          </p>

                          <p className="mt-1 text-xs text-slate-500">
                            {supplier.branch.code}
                          </p>

                          {supplier.branch
                            .is_head_office && (
                            <span className="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                              Head office
                            </span>
                          )}
                        </>
                      ) : (
                        <span className="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                          Company-wide
                        </span>
                      )}
                    </td>

                    <td className="px-5 py-4 text-sm text-slate-600">
                      <p>
                        {supplier.email ?? "—"}
                      </p>

                      <p className="mt-1">
                        {supplier.phone ?? "—"}
                      </p>

                      {supplier.alternate_phone && (
                        <p className="mt-1 text-xs text-slate-500">
                          Alt:{" "}
                          {supplier.alternate_phone}
                        </p>
                      )}
                    </td>

                    <td className="px-5 py-4">
                      <p className="text-sm font-semibold text-slate-700">
                        {
                          supplier.payment_term_days
                        }{" "}
                        days
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        Credit limit:{" "}
                        {formatMoney(
                          supplier.credit_limit
                        )}
                      </p>
                    </td>

                    <td className="px-5 py-4">
                      <p className="text-sm font-semibold text-slate-700">
                        {formatMoney(
                          supplier.opening_balance
                        )}
                      </p>

                      <span
                        className={`mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                          supplier.opening_balance_type ===
                          "payable"
                            ? "bg-amber-100 text-amber-800"
                            : "bg-blue-100 text-blue-800"
                        }`}
                      >
                        {supplier.opening_balance_type ===
                        "payable"
                          ? "Payable"
                          : "Receivable"}
                      </span>
                    </td>

                    <td className="px-5 py-4">
                      <span
                        className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                          supplier.is_active
                            ? "bg-emerald-100 text-emerald-800"
                            : "bg-red-100 text-red-800"
                        }`}
                      >
                        {supplier.is_active
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
                                  supplier
                                )
                              }
                              disabled={
                                updatingSupplierId ===
                                  supplier.id ||
                                deletingSupplierId ===
                                  supplier.id
                              }
                              className="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                              Edit
                            </button>

                            <button
                              type="button"
                              onClick={() =>
                                void handleStatusChange(
                                  supplier
                                )
                              }
                              disabled={
                                updatingSupplierId ===
                                  supplier.id ||
                                deletingSupplierId ===
                                  supplier.id
                              }
                              className="rounded-md border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                              {updatingSupplierId ===
                              supplier.id
                                ? "Updating..."
                                : supplier.is_active
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
                                supplier
                              )
                            }
                            disabled={
                              deletingSupplierId ===
                                supplier.id ||
                              updatingSupplierId ===
                                supplier.id
                            }
                            className="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                          >
                            {deletingSupplierId ===
                            supplier.id
                              ? "Deleting..."
                              : "Delete"}
                          </button>
                        )}

                        {!canUpdate &&
                          !canDelete && (
                            <span className="text-xs text-slate-400">
                              View only
                            </span>
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
            {pagination.total} suppliers
          </p>

          <div className="flex gap-2">
            <button
              type="button"
              disabled={
                isLoading ||
                pagination.current_page <= 1
              }
              onClick={() =>
                void loadSuppliers(
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
                void loadSuppliers(
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
        <SupplierForm
          key={
            editingSupplier?.id ??
            "new-supplier"
          }
          supplier={editingSupplier}
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