"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";

import { useAuth } from "@/contexts/AuthContext";
import { branchService } from "@/services/branchService";
import type { ApiErrorResponse } from "@/types/auth";
import type {
  Branch,
  BranchPagination,
  BranchPayload,
  BranchQuery,
} from "@/types/branch";

interface BranchFormValues {
  name: string;
  code: string;
  email: string;
  phone: string;
  address: string;
  city: string;
  district: string;
  postalCode: string;
  isHeadOffice: boolean;
  isActive: boolean;
}

interface BranchFormProps {
  branch: Branch | null;
  isSaving: boolean;
  errors: Record<string, string>;
  onCancel: () => void;
  onSubmit: (
    payload: BranchPayload
  ) => Promise<void>;
}

const emptyPagination: BranchPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
};

const emptyForm: BranchFormValues = {
  name: "",
  code: "",
  email: "",
  phone: "",
  address: "",
  city: "",
  district: "",
  postalCode: "",
  isHeadOffice: false,
  isActive: true,
};

function nullableValue(
  value: string
): string | null {
  const trimmedValue = value.trim();

  return trimmedValue.length > 0
    ? trimmedValue
    : null;
}

function branchToForm(
  branch: Branch | null
): BranchFormValues {
  if (!branch) {
    return emptyForm;
  }

  return {
    name: branch.name,
    code: branch.code,
    email: branch.email ?? "",
    phone: branch.phone ?? "",
    address: branch.address ?? "",
    city: branch.city ?? "",
    district: branch.district ?? "",
    postalCode: branch.postal_code ?? "",
    isHeadOffice: branch.is_head_office,
    isActive: branch.is_active,
  };
}

function getApiMessage(
  error: unknown,
  fallback: string
): string {
  const axiosError =
    error as AxiosError<ApiErrorResponse>;

  return axiosError.response?.data?.message ??
    fallback;
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

function BranchForm({
  branch,
  isSaving,
  errors,
  onCancel,
  onSubmit,
}: BranchFormProps) {
  const [form, setForm] =
    useState<BranchFormValues>(
      branchToForm(branch)
    );

  function updateField<
    Key extends keyof BranchFormValues
  >(
    field: Key,
    value: BranchFormValues[Key]
  ): void {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  }

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    await onSubmit({
      name: form.name.trim(),
      code: form.code
        .trim()
        .toUpperCase()
        .replace(/\s+/g, "-"),
      email: nullableValue(form.email),
      phone: nullableValue(form.phone),
      address: nullableValue(form.address),
      city: nullableValue(form.city),
      district: nullableValue(form.district),
      postal_code: nullableValue(
        form.postalCode
      ),
      is_head_office: form.isHeadOffice,
      is_active: form.isActive,
    });
  }

  return (
    <div className="fixed inset-0 z-[70] overflow-y-auto bg-slate-950/60 px-4 py-8">
      <div className="mx-auto w-full max-w-3xl rounded-2xl bg-white shadow-xl">
        <div className="flex items-start justify-between border-b border-slate-200 px-6 py-5">
          <div>
            <h2 className="text-xl font-bold text-slate-950">
              {branch
                ? "Edit branch"
                : "Create branch"}
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              {branch
                ? "Update branch information and operational status."
                : "Add a new branch under your assigned company."}
            </p>
          </div>

          <button
            type="button"
            onClick={onCancel}
            disabled={isSaving}
            aria-label="Close form"
            className="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100"
          >
            ✕
          </button>
        </div>

        <form
          onSubmit={handleSubmit}
          className="p-6"
        >
          <div className="grid gap-5 md:grid-cols-2">
            <div>
              <label
                htmlFor="branch-name"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Branch name
              </label>

              <input
                id="branch-name"
                required
                maxLength={255}
                value={form.name}
                onChange={(event) =>
                  updateField(
                    "name",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              />

              {errors.name && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.name}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="branch-code"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Branch code
              </label>

              <input
                id="branch-code"
                required
                maxLength={50}
                value={form.code}
                onChange={(event) =>
                  updateField(
                    "code",
                    event.target.value.toUpperCase()
                  )
                }
                disabled={isSaving}
                placeholder="DHAKA-BRANCH"
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 uppercase outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              />

              {errors.code && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.code}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="branch-email"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Email
              </label>

              <input
                id="branch-email"
                type="email"
                maxLength={255}
                value={form.email}
                onChange={(event) =>
                  updateField(
                    "email",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              />

              {errors.email && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.email}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="branch-phone"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Phone
              </label>

              <input
                id="branch-phone"
                maxLength={30}
                value={form.phone}
                onChange={(event) =>
                  updateField(
                    "phone",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              />

              {errors.phone && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.phone}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="branch-city"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                City
              </label>

              <input
                id="branch-city"
                maxLength={100}
                value={form.city}
                onChange={(event) =>
                  updateField(
                    "city",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              />
            </div>

            <div>
              <label
                htmlFor="branch-district"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                District
              </label>

              <input
                id="branch-district"
                maxLength={100}
                value={form.district}
                onChange={(event) =>
                  updateField(
                    "district",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              />
            </div>

            <div>
              <label
                htmlFor="branch-postal-code"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Postal code
              </label>

              <input
                id="branch-postal-code"
                maxLength={20}
                value={form.postalCode}
                onChange={(event) =>
                  updateField(
                    "postalCode",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              />
            </div>

            <div className="flex items-center gap-6 pt-7">
              <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input
                  type="checkbox"
                  checked={form.isHeadOffice}
                  onChange={(event) =>
                    updateField(
                      "isHeadOffice",
                      event.target.checked
                    )
                  }
                  disabled={isSaving}
                  className="h-4 w-4 rounded border-slate-300"
                />

                Head office
              </label>

              <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input
                  type="checkbox"
                  checked={form.isActive}
                  onChange={(event) =>
                    updateField(
                      "isActive",
                      event.target.checked
                    )
                  }
                  disabled={isSaving}
                  className="h-4 w-4 rounded border-slate-300"
                />

                Active
              </label>
            </div>

            <div className="md:col-span-2">
              <label
                htmlFor="branch-address"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Address
              </label>

              <textarea
                id="branch-address"
                rows={4}
                maxLength={2000}
                value={form.address}
                onChange={(event) =>
                  updateField(
                    "address",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="w-full rounded-lg border border-slate-300 px-3.5 py-3 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              />

              {errors.address && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.address}
                </p>
              )}
            </div>
          </div>

          <div className="mt-7 flex justify-end gap-3 border-t border-slate-200 pt-5">
            <button
              type="button"
              onClick={onCancel}
              disabled={isSaving}
              className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
              Cancel
            </button>

            <button
              type="submit"
              disabled={isSaving}
              className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:bg-emerald-400"
            >
              {isSaving
                ? "Saving..."
                : branch
                  ? "Save changes"
                  : "Create branch"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default function BranchesPage() {
  const { user, refreshUser } = useAuth();

  const [branches, setBranches] =
    useState<Branch[]>([]);

  const [pagination, setPagination] =
    useState<BranchPagination>(
      emptyPagination
    );

  const [search, setSearch] = useState("");
  const [activeFilter, setActiveFilter] =
    useState<"all" | "active" | "inactive">(
      "all"
    );

  const [editingBranch, setEditingBranch] =
    useState<Branch | null>(null);

  const [isFormOpen, setIsFormOpen] =
    useState(false);

  const [isLoading, setIsLoading] =
    useState(true);

  const [isSaving, setIsSaving] =
    useState(false);

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
        permission.code === "branch.create"
    ) ?? false;

  const canUpdate =
    user?.permissions.some(
      (permission) =>
        permission.code === "branch.update"
    ) ?? false;

  const canDelete =
    user?.permissions.some(
      (permission) =>
        permission.code === "branch.delete"
    ) ?? false;

  useEffect(() => {
    let isActive = true;

    branchService
      .getBranches({
        page: 1,
        per_page: 10,
        sort_by: "name",
        sort_direction: "asc",
      })
      .then((data) => {
        if (!isActive) {
          return;
        }

        setBranches(data.branches);
        setPagination(data.pagination);
      })
      .catch((error: unknown) => {
        if (isActive) {
          setGeneralError(
            getApiMessage(
              error,
              "Unable to load branches."
            )
          );
        }
      })
      .finally(() => {
        if (isActive) {
          setIsLoading(false);
        }
      });

    return () => {
      isActive = false;
    };
  }, []);

  async function loadBranches(
    page = 1
  ): Promise<void> {
    setIsLoading(true);
    setGeneralError(null);

    const query: BranchQuery = {
      search: search.trim() || undefined,
      is_active:
        activeFilter === "all"
          ? undefined
          : activeFilter === "active",
      page,
      per_page: 10,
      sort_by: "name",
      sort_direction: "asc",
    };

    try {
      const data =
        await branchService.getBranches(query);

      setBranches(data.branches);
      setPagination(data.pagination);
    } catch (error) {
      setGeneralError(
        getApiMessage(
          error,
          "Unable to load branches."
        )
      );
    } finally {
      setIsLoading(false);
    }
  }

  async function handleSearch(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    await loadBranches(1);
  }

  function openCreateForm(): void {
    setEditingBranch(null);
    setFormErrors({});
    setGeneralError(null);
    setSuccessMessage(null);
    setIsFormOpen(true);
  }

  function openEditForm(
    branch: Branch
  ): void {
    setEditingBranch(branch);
    setFormErrors({});
    setGeneralError(null);
    setSuccessMessage(null);
    setIsFormOpen(true);
  }

  async function handleSave(
    payload: BranchPayload
  ): Promise<void> {
    setIsSaving(true);
    setFormErrors({});
    setGeneralError(null);

    try {
      if (editingBranch) {
        await branchService.updateBranch(
          editingBranch.id,
          payload
        );

        setSuccessMessage(
          "Branch updated successfully."
        );
      } else {
        await branchService.createBranch(
          payload
        );

        setSuccessMessage(
          "Branch created successfully."
        );
      }

      setIsFormOpen(false);
      setEditingBranch(null);

      await Promise.all([
        loadBranches(
          pagination.current_page
        ),
        refreshUser(),
      ]);
    } catch (error) {
      setFormErrors(
        getValidationErrors(error)
      );

      setGeneralError(
        getApiMessage(
          error,
          "Unable to save the branch."
        )
      );
    } finally {
      setIsSaving(false);
    }
  }

  async function handleStatusChange(
    branch: Branch
  ): Promise<void> {
    setGeneralError(null);
    setSuccessMessage(null);

    try {
      await branchService.updateBranch(
        branch.id,
        {
          is_active: !branch.is_active,
        }
      );

      setSuccessMessage(
        branch.is_active
          ? "Branch deactivated successfully."
          : "Branch activated successfully."
      );

      await loadBranches(
        pagination.current_page
      );
    } catch (error) {
      setGeneralError(
        getApiMessage(
          error,
          "Unable to update branch status."
        )
      );
    }
  }

  async function handleDelete(
    branch: Branch
  ): Promise<void> {
    const confirmed = window.confirm(
      `Delete "${branch.name}"? This action soft-deletes the branch.`
    );

    if (!confirmed) {
      return;
    }

    setDeletingId(branch.id);
    setGeneralError(null);
    setSuccessMessage(null);

    try {
      await branchService.deleteBranch(
        branch.id
      );

      setSuccessMessage(
        "Branch deleted successfully."
      );

      await Promise.all([
        loadBranches(1),
        refreshUser(),
      ]);
    } catch (error) {
      setGeneralError(
        getApiMessage(
          error,
          "Unable to delete the branch."
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
            Branch Management
          </h2>

          <p className="mt-2 text-sm text-slate-500">
            Manage company branches and branch-level
            operational access.
          </p>
        </div>

        {canCreate && (
          <button
            type="button"
            onClick={openCreateForm}
            className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
          >
            Add branch
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
          className="grid gap-4 border-b border-slate-200 p-5 md:grid-cols-[1fr_200px_auto]"
        >
          <input
            type="search"
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
            placeholder="Search by name, code, city or district"
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

          <button
            type="submit"
            disabled={isLoading}
            className="h-11 rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white hover:bg-slate-800 disabled:bg-slate-500"
          >
            Search
          </button>
        </form>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                {[
                  "Branch",
                  "Location",
                  "Contact",
                  "Warehouses",
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
                    Loading branches...
                  </td>
                </tr>
              ) : branches.length === 0 ? (
                <tr>
                  <td
                    colSpan={7}
                    className="px-5 py-12 text-center text-sm text-slate-500"
                  >
                    No branches found.
                  </td>
                </tr>
              ) : (
                branches.map((branch) => (
                  <tr
                    key={branch.id}
                    className="align-top"
                  >
                    <td className="px-5 py-4">
                      <p className="font-semibold text-slate-900">
                        {branch.name}
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        {branch.code}
                      </p>

                      {branch.is_head_office && (
                        <span className="mt-2 inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                          Head office
                        </span>
                      )}
                    </td>

                    <td className="px-5 py-4 text-sm text-slate-600">
                      <p>
                        {branch.city ??
                          "No city"}
                      </p>
                      <p className="mt-1 text-xs text-slate-500">
                        {branch.district ??
                          "No district"}
                      </p>
                    </td>

                    <td className="px-5 py-4 text-sm text-slate-600">
                      <p>
                        {branch.email ?? "—"}
                      </p>
                      <p className="mt-1">
                        {branch.phone ?? "—"}
                      </p>
                    </td>

                    <td className="px-5 py-4 text-sm font-semibold text-slate-700">
                      {branch.warehouses_count}
                    </td>

                    <td className="px-5 py-4 text-sm font-semibold text-slate-700">
                      {branch.users_count}
                    </td>

                    <td className="px-5 py-4">
                      <span
                        className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                          branch.is_active
                            ? "bg-emerald-100 text-emerald-800"
                            : "bg-red-100 text-red-800"
                        }`}
                      >
                        {branch.is_active
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
                                  branch
                                )
                              }
                              className="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >
                              Edit
                            </button>

                            <button
                              type="button"
                              onClick={() =>
                                void handleStatusChange(
                                  branch
                                )
                              }
                              className="rounded-md border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50"
                            >
                              {branch.is_active
                                ? "Deactivate"
                                : "Activate"}
                            </button>
                          </>
                        )}

                        {canDelete &&
                          !branch.is_head_office && (
                            <button
                              type="button"
                              onClick={() =>
                                void handleDelete(
                                  branch
                                )
                              }
                              disabled={
                                deletingId ===
                                branch.id
                              }
                              className="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
                            >
                              {deletingId ===
                              branch.id
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
            {pagination.total} branches
          </p>

          <div className="flex gap-2">
            <button
              type="button"
              disabled={
                isLoading ||
                pagination.current_page <= 1
              }
              onClick={() =>
                void loadBranches(
                  pagination.current_page - 1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Previous
            </button>

            <span className="flex items-center px-3 text-sm text-slate-600">
              Page {pagination.current_page} of{" "}
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
                void loadBranches(
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
        <BranchForm
          key={
            editingBranch?.id ??
            "new-branch"
          }
          branch={editingBranch}
          isSaving={isSaving}
          errors={formErrors}
          onCancel={() => {
            if (!isSaving) {
              setIsFormOpen(false);
              setEditingBranch(null);
            }
          }}
          onSubmit={handleSave}
        />
      )}
    </div>
  );
}