"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";

import { SupplierContactForm } from "@/components/suppliers/SupplierContactForm";
import { SupplierForm } from "@/components/suppliers/SupplierForm";
import { useAuth } from "@/contexts/AuthContext";
import { branchService } from "@/services/branchService";
import { supplierService } from "@/services/supplierService";
import type { ApiErrorResponse } from "@/types/auth";
import type { Branch } from "@/types/branch";
import type {
  Supplier,
  SupplierContact,
  SupplierContactPagination,
  SupplierContactPayload,
  SupplierContactQuery,
  SupplierContactType,
  SupplierOpeningBalanceType,
  SupplierPagination,
  SupplierPayload,
  SupplierQuery,
} from "@/types/supplier";

const emptySupplierPagination: SupplierPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
};

const emptyContactPagination: SupplierContactPagination = {
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

function formatContactType(
  type: SupplierContactType
): string {
  return (
    type.charAt(0).toUpperCase() +
    type.slice(1)
  );
}

export default function SuppliersPage() {
  const { user } = useAuth();

  const [suppliers, setSuppliers] =
    useState<Supplier[]>([]);

  const [branches, setBranches] =
    useState<Branch[]>([]);

  const [
    supplierPagination,
    setSupplierPagination,
  ] = useState<SupplierPagination>(
    emptySupplierPagination
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

  const [
    isSupplierFormOpen,
    setIsSupplierFormOpen,
  ] = useState(false);

  const [
    selectedSupplier,
    setSelectedSupplier,
  ] = useState<Supplier | null>(null);

  const [
    supplierContacts,
    setSupplierContacts,
  ] = useState<SupplierContact[]>([]);

  const [
    contactPagination,
    setContactPagination,
  ] = useState<SupplierContactPagination>(
    emptyContactPagination
  );

  const [
    contactSearch,
    setContactSearch,
  ] = useState("");

  const [
    contactTypeFilter,
    setContactTypeFilter,
  ] = useState<
    "all" | SupplierContactType
  >("all");

  const [
    contactStatusFilter,
    setContactStatusFilter,
  ] = useState<
    "all" | "active" | "inactive"
  >("all");

  const [
    primaryContactFilter,
    setPrimaryContactFilter,
  ] = useState<
    "all" | "primary" | "non-primary"
  >("all");

  const [
    editingContact,
    setEditingContact,
  ] = useState<SupplierContact | null>(
    null
  );

  const [
    isContactFormOpen,
    setIsContactFormOpen,
  ] = useState(false);

  const [
    isSupplierLoading,
    setIsSupplierLoading,
  ] = useState(true);

  const [
    isContactLoading,
    setIsContactLoading,
  ] = useState(false);

  const [
    isSupplierSaving,
    setIsSupplierSaving,
  ] = useState(false);

  const [
    isContactSaving,
    setIsContactSaving,
  ] = useState(false);

  const [
    updatingSupplierId,
    setUpdatingSupplierId,
  ] = useState<number | null>(null);

  const [
    deletingSupplierId,
    setDeletingSupplierId,
  ] = useState<number | null>(null);

  const [
    updatingContactId,
    setUpdatingContactId,
  ] = useState<number | null>(null);

  const [
    deletingContactId,
    setDeletingContactId,
  ] = useState<number | null>(null);

  const [
    supplierFormErrors,
    setSupplierFormErrors,
  ] = useState<Record<string, string>>(
    {}
  );

  const [
    contactFormErrors,
    setContactFormErrors,
  ] = useState<Record<string, string>>(
    {}
  );

  const [
    errorMessage,
    setErrorMessage,
  ] = useState<string | null>(null);

  const [
    successMessage,
    setSuccessMessage,
  ] = useState<string | null>(null);

  const canCreateSupplier =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "supplier.create"
    ) ?? false;

  const canUpdateSupplier =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "supplier.update"
    ) ?? false;

  const canDeleteSupplier =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "supplier.delete"
    ) ?? false;

  const canViewContacts =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "supplier-contact.view"
    ) ?? false;

  const canCreateContact =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "supplier-contact.create"
    ) ?? false;

  const canUpdateContact =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "supplier-contact.update"
    ) ?? false;

  const canDeleteContact =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "supplier-contact.delete"
    ) ?? false;

  async function loadSuppliers(
    page = 1
  ): Promise<void> {
    setIsSupplierLoading(true);
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
      setSupplierPagination(
        data.pagination
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load suppliers."
        )
      );
    } finally {
      setIsSupplierLoading(false);
    }
  }

  async function loadSupplierContacts(
    supplier: Supplier,
    page = 1
  ): Promise<void> {
    setIsContactLoading(true);
    setErrorMessage(null);

    const query: SupplierContactQuery = {
      supplier_id: supplier.id,

      search:
        contactSearch.trim() || undefined,

      contact_type:
        contactTypeFilter === "all"
          ? undefined
          : contactTypeFilter,

      is_active:
        contactStatusFilter === "all"
          ? undefined
          : contactStatusFilter ===
              "active",

      is_primary:
        primaryContactFilter === "all"
          ? undefined
          : primaryContactFilter ===
              "primary",

      page,
      per_page: 10,
      sort_by: "name",
      sort_direction: "asc",
    };

    try {
      const data =
        await supplierService.getSupplierContacts(
          query
        );

      setSupplierContacts(
        data.supplier_contacts
      );

      setContactPagination(
        data.pagination
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load supplier contacts."
        )
      );
    } finally {
      setIsContactLoading(false);
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
        setSupplierPagination(
          data.pagination
        );
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
          setIsSupplierLoading(false);
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

        setBranches(
          data.branches.filter(
            (branch) => branch.is_active
          )
        );
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

  async function handleSupplierSearch(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    await loadSuppliers(1);
  }

  async function handleContactSearch(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    if (!selectedSupplier) {
      return;
    }

    await loadSupplierContacts(
      selectedSupplier,
      1
    );
  }

  function openCreateSupplierForm(): void {
    setEditingSupplier(null);
    setSupplierFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsSupplierFormOpen(true);
  }

  function openEditSupplierForm(
    supplier: Supplier
  ): void {
    setEditingSupplier(supplier);
    setSupplierFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsSupplierFormOpen(true);
  }

  function closeSupplierForm(): void {
    if (isSupplierSaving) {
      return;
    }

    setIsSupplierFormOpen(false);
    setEditingSupplier(null);
    setSupplierFormErrors({});
  }

  async function openContactsPanel(
    supplier: Supplier
  ): Promise<void> {
    setSelectedSupplier(supplier);
    setSupplierContacts([]);
    setContactPagination(
      emptyContactPagination
    );
    setContactSearch("");
    setContactTypeFilter("all");
    setContactStatusFilter("all");
    setPrimaryContactFilter("all");
    setErrorMessage(null);
    setSuccessMessage(null);

    await loadSupplierContacts(
      supplier,
      1
    );
  }

  function closeContactsPanel(): void {
    if (
      isContactSaving ||
      updatingContactId !== null ||
      deletingContactId !== null
    ) {
      return;
    }

    setSelectedSupplier(null);
    setSupplierContacts([]);
    setContactPagination(
      emptyContactPagination
    );
    setEditingContact(null);
    setIsContactFormOpen(false);
    setContactFormErrors({});
  }

  function openCreateContactForm(): void {
    setEditingContact(null);
    setContactFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsContactFormOpen(true);
  }

  function openEditContactForm(
    contact: SupplierContact
  ): void {
    setEditingContact(contact);
    setContactFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsContactFormOpen(true);
  }

  function closeContactForm(): void {
    if (isContactSaving) {
      return;
    }

    setIsContactFormOpen(false);
    setEditingContact(null);
    setContactFormErrors({});
  }

  async function handleSupplierSave(
    payload: SupplierPayload
  ): Promise<void> {
    setIsSupplierSaving(true);
    setSupplierFormErrors({});
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

      setIsSupplierFormOpen(false);
      setEditingSupplier(null);

      await loadSuppliers(
        supplierPagination.current_page
      );
    } catch (error) {
      setSupplierFormErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to save the supplier."
        )
      );
    } finally {
      setIsSupplierSaving(false);
    }
  }

  async function handleContactSave(
    payload: SupplierContactPayload
  ): Promise<void> {
    if (!selectedSupplier) {
      return;
    }

    setIsContactSaving(true);
    setContactFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      if (editingContact) {
        await supplierService.updateSupplierContact(
          editingContact.id,
          {
            name: payload.name,
            designation:
              payload.designation,
            department:
              payload.department,
            contact_type:
              payload.contact_type,
            email: payload.email,
            phone: payload.phone,
            alternate_phone:
              payload.alternate_phone,
            is_primary:
              payload.is_primary,
            is_active:
              payload.is_active,
            notes: payload.notes,
          }
        );

        setSuccessMessage(
          "Supplier contact updated successfully."
        );
      } else {
        await supplierService.createSupplierContact(
          payload
        );

        setSuccessMessage(
          "Supplier contact created successfully."
        );
      }

      setIsContactFormOpen(false);
      setEditingContact(null);

      await loadSupplierContacts(
        selectedSupplier,
        contactPagination.current_page
      );
    } catch (error) {
      setContactFormErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to save the supplier contact."
        )
      );
    } finally {
      setIsContactSaving(false);
    }
  }

  async function handleSupplierStatusChange(
    supplier: Supplier
  ): Promise<void> {
    setUpdatingSupplierId(supplier.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await supplierService.updateSupplier(
        supplier.id,
        {
          is_active:
            !supplier.is_active,
        }
      );

      setSuccessMessage(
        supplier.is_active
          ? "Supplier deactivated successfully."
          : "Supplier activated successfully."
      );

      await loadSuppliers(
        supplierPagination.current_page
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

  async function handleContactStatusChange(
    contact: SupplierContact
  ): Promise<void> {
    if (!selectedSupplier) {
      return;
    }

    setUpdatingContactId(contact.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await supplierService.updateSupplierContact(
        contact.id,
        {
          is_active:
            !contact.is_active,
        }
      );

      setSuccessMessage(
        contact.is_active
          ? "Supplier contact deactivated successfully."
          : "Supplier contact activated successfully."
      );

      await loadSupplierContacts(
        selectedSupplier,
        contactPagination.current_page
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to update supplier contact status."
        )
      );
    } finally {
      setUpdatingContactId(null);
    }
  }

  async function handleMakePrimary(
    contact: SupplierContact
  ): Promise<void> {
    if (
      !selectedSupplier ||
      contact.is_primary
    ) {
      return;
    }

    setUpdatingContactId(contact.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await supplierService.updateSupplierContact(
        contact.id,
        {
          is_primary: true,
        }
      );

      setSuccessMessage(
        "Primary supplier contact updated successfully."
      );

      await loadSupplierContacts(
        selectedSupplier,
        contactPagination.current_page
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to update the primary contact."
        )
      );
    } finally {
      setUpdatingContactId(null);
    }
  }

  async function handleSupplierDelete(
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
        supplierPagination.current_page >
          1
          ? supplierPagination.current_page -
            1
          : supplierPagination.current_page;

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

  async function handleContactDelete(
    contact: SupplierContact
  ): Promise<void> {
    if (!selectedSupplier) {
      return;
    }

    const confirmed = window.confirm(
      `Delete "${contact.name}"? This will soft-delete the supplier contact.`
    );

    if (!confirmed) {
      return;
    }

    setDeletingContactId(contact.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await supplierService.deleteSupplierContact(
        contact.id
      );

      setSuccessMessage(
        "Supplier contact deleted successfully."
      );

      const nextPage =
        supplierContacts.length === 1 &&
        contactPagination.current_page > 1
          ? contactPagination.current_page -
            1
          : contactPagination.current_page;

      await loadSupplierContacts(
        selectedSupplier,
        nextPage
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to delete the supplier contact."
        )
      );
    } finally {
      setDeletingContactId(null);
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
            Manage supplier details,
            contacts, branch access,
            payment terms, balances, and
            operational status.
          </p>
        </div>

        {canCreateSupplier && (
          <button
            type="button"
            onClick={openCreateSupplierForm}
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
          onSubmit={handleSupplierSearch}
          className="grid gap-4 border-b border-slate-200 p-5 xl:grid-cols-[1fr_180px_180px_190px_auto]"
        >
          <input
            type="search"
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
            placeholder="Search supplier name, code, email or phone"
            className="h-11 rounded-lg border border-slate-300 bg-white px-4 text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
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
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
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
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
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
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
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
            disabled={isSupplierLoading}
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
              {isSupplierLoading ? (
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
                          {
                            supplier.business_name
                          }
                        </p>
                      )}
                    </td>

                    <td className="px-5 py-4">
                      {supplier.branch ? (
                        <>
                          <p className="text-sm font-medium text-slate-700">
                            {
                              supplier.branch
                                .name
                            }
                          </p>

                          <p className="mt-1 text-xs text-slate-500">
                            {
                              supplier.branch
                                .code
                            }
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
                          {
                            supplier.alternate_phone
                          }
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
                        {canViewContacts && (
                          <button
                            type="button"
                            onClick={() =>
                              void openContactsPanel(
                                supplier
                              )
                            }
                            className="rounded-md border border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                          >
                            Contacts
                          </button>
                        )}

                        {canUpdateSupplier && (
                          <>
                            <button
                              type="button"
                              onClick={() =>
                                openEditSupplierForm(
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
                                void handleSupplierStatusChange(
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

                        {canDeleteSupplier && (
                          <button
                            type="button"
                            onClick={() =>
                              void handleSupplierDelete(
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

                        {!canViewContacts &&
                          !canUpdateSupplier &&
                          !canDeleteSupplier && (
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
            Showing{" "}
            {supplierPagination.from ?? 0}–
            {supplierPagination.to ?? 0} of{" "}
            {supplierPagination.total} suppliers
          </p>

          <div className="flex gap-2">
            <button
              type="button"
              disabled={
                isSupplierLoading ||
                supplierPagination.current_page <=
                  1
              }
              onClick={() =>
                void loadSuppliers(
                  supplierPagination.current_page -
                    1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Previous
            </button>

            <span className="flex items-center px-3 text-sm text-slate-600">
              Page{" "}
              {supplierPagination.current_page}{" "}
              of{" "}
              {supplierPagination.last_page}
            </span>

            <button
              type="button"
              disabled={
                isSupplierLoading ||
                supplierPagination.current_page >=
                  supplierPagination.last_page
              }
              onClick={() =>
                void loadSuppliers(
                  supplierPagination.current_page +
                    1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </section>

      {isSupplierFormOpen && (
        <SupplierForm
          key={
            editingSupplier?.id ??
            "new-supplier"
          }
          supplier={editingSupplier}
          branches={branches}
          isSaving={isSupplierSaving}
          errors={supplierFormErrors}
          onCancel={closeSupplierForm}
          onSubmit={handleSupplierSave}
        />
      )}

      {selectedSupplier && (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/60 p-4">
          <div className="max-h-[94vh] w-full max-w-7xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div className="flex flex-col justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center">
              <div>
                <h2 className="text-xl font-bold text-slate-950">
                  Supplier Contacts
                </h2>

                <p className="mt-1 text-sm text-slate-500">
                  {selectedSupplier.name} ·{" "}
                  {selectedSupplier.code}
                </p>
              </div>

              <div className="flex flex-wrap gap-3">
                {canCreateContact && (
                  <button
                    type="button"
                    onClick={openCreateContactForm}
                    className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                  >
                    Add contact
                  </button>
                )}

                <button
                  type="button"
                  onClick={closeContactsPanel}
                  className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                  Close
                </button>
              </div>
            </div>

            <form
              onSubmit={handleContactSearch}
              className="grid gap-4 border-b border-slate-200 p-5 xl:grid-cols-[1fr_170px_170px_180px_auto]"
            >
              <input
                type="search"
                value={contactSearch}
                onChange={(event) =>
                  setContactSearch(
                    event.target.value
                  )
                }
                placeholder="Search contact name, email, phone or department"
                className="h-11 rounded-lg border border-slate-300 bg-white px-4 text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              />

              <select
                value={contactTypeFilter}
                onChange={(event) =>
                  setContactTypeFilter(
                    event.target.value as
                      | "all"
                      | SupplierContactType
                  )
                }
                className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              >
                <option value="all">
                  All types
                </option>

                <option value="general">
                  General
                </option>

                <option value="sales">
                  Sales
                </option>

                <option value="accounts">
                  Accounts
                </option>

                <option value="support">
                  Support
                </option>

                <option value="management">
                  Management
                </option>
              </select>

              <select
                value={contactStatusFilter}
                onChange={(event) =>
                  setContactStatusFilter(
                    event.target.value as
                      | "all"
                      | "active"
                      | "inactive"
                  )
                }
                className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
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
                value={primaryContactFilter}
                onChange={(event) =>
                  setPrimaryContactFilter(
                    event.target.value as
                      | "all"
                      | "primary"
                      | "non-primary"
                  )
                }
                className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
              >
                <option value="all">
                  All contacts
                </option>

                <option value="primary">
                  Primary only
                </option>

                <option value="non-primary">
                  Non-primary
                </option>
              </select>

              <button
                type="submit"
                disabled={isContactLoading}
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
                      "Contact",
                      "Type",
                      "Communication",
                      "Primary",
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
                  {isContactLoading ? (
                    <tr>
                      <td
                        colSpan={6}
                        className="px-5 py-12 text-center text-sm text-slate-500"
                      >
                        Loading supplier contacts...
                      </td>
                    </tr>
                  ) : supplierContacts.length ===
                    0 ? (
                    <tr>
                      <td
                        colSpan={6}
                        className="px-5 py-12 text-center text-sm text-slate-500"
                      >
                        No supplier contacts found.
                      </td>
                    </tr>
                  ) : (
                    supplierContacts.map(
                      (contact) => (
                        <tr
                          key={contact.id}
                          className="align-top"
                        >
                          <td className="px-5 py-4">
                            <p className="font-semibold text-slate-900">
                              {contact.name}
                            </p>

                            <p className="mt-1 text-xs text-slate-500">
                              {contact.designation ??
                                "No designation"}
                            </p>

                            {contact.department && (
                              <p className="mt-1 text-xs text-slate-500">
                                {
                                  contact.department
                                }
                              </p>
                            )}
                          </td>

                          <td className="px-5 py-4">
                            <span className="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                              {formatContactType(
                                contact.contact_type
                              )}
                            </span>
                          </td>

                          <td className="px-5 py-4 text-sm text-slate-600">
                            <p>
                              {contact.email ??
                                "—"}
                            </p>

                            <p className="mt-1">
                              {contact.phone ??
                                "—"}
                            </p>

                            {contact.alternate_phone && (
                              <p className="mt-1 text-xs text-slate-500">
                                Alt:{" "}
                                {
                                  contact.alternate_phone
                                }
                              </p>
                            )}
                          </td>

                          <td className="px-5 py-4">
                            {contact.is_primary ? (
                              <span className="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800">
                                Primary
                              </span>
                            ) : (
                              <span className="text-xs text-slate-400">
                                Standard
                              </span>
                            )}
                          </td>

                          <td className="px-5 py-4">
                            <span
                              className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                contact.is_active
                                  ? "bg-emerald-100 text-emerald-800"
                                  : "bg-red-100 text-red-800"
                              }`}
                            >
                              {contact.is_active
                                ? "Active"
                                : "Inactive"}
                            </span>
                          </td>

                          <td className="px-5 py-4">
                            <div className="flex flex-wrap gap-2">
                              {canUpdateContact && (
                                <>
                                  <button
                                    type="button"
                                    onClick={() =>
                                      openEditContactForm(
                                        contact
                                      )
                                    }
                                    disabled={
                                      updatingContactId ===
                                        contact.id ||
                                      deletingContactId ===
                                        contact.id
                                    }
                                    className="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                  >
                                    Edit
                                  </button>

                                  {!contact.is_primary && (
                                    <button
                                      type="button"
                                      onClick={() =>
                                        void handleMakePrimary(
                                          contact
                                        )
                                      }
                                      disabled={
                                        updatingContactId ===
                                          contact.id ||
                                        deletingContactId ===
                                          contact.id
                                      }
                                      className="rounded-md border border-violet-300 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                      Make primary
                                    </button>
                                  )}

                                  <button
                                    type="button"
                                    onClick={() =>
                                      void handleContactStatusChange(
                                        contact
                                      )
                                    }
                                    disabled={
                                      updatingContactId ===
                                        contact.id ||
                                      deletingContactId ===
                                        contact.id
                                    }
                                    className="rounded-md border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50"
                                  >
                                    {updatingContactId ===
                                    contact.id
                                      ? "Updating..."
                                      : contact.is_active
                                        ? "Deactivate"
                                        : "Activate"}
                                  </button>
                                </>
                              )}

                              {canDeleteContact && (
                                <button
                                  type="button"
                                  onClick={() =>
                                    void handleContactDelete(
                                      contact
                                    )
                                  }
                                  disabled={
                                    deletingContactId ===
                                      contact.id ||
                                    updatingContactId ===
                                      contact.id
                                  }
                                  className="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                  {deletingContactId ===
                                  contact.id
                                    ? "Deleting..."
                                    : "Delete"}
                                </button>
                              )}

                              {!canUpdateContact &&
                                !canDeleteContact && (
                                  <span className="text-xs text-slate-400">
                                    View only
                                  </span>
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
                Showing{" "}
                {contactPagination.from ?? 0}–
                {contactPagination.to ?? 0} of{" "}
                {contactPagination.total} contacts
              </p>

              <div className="flex gap-2">
                <button
                  type="button"
                  disabled={
                    isContactLoading ||
                    contactPagination.current_page <=
                      1
                  }
                  onClick={() =>
                    void loadSupplierContacts(
                      selectedSupplier,
                      contactPagination.current_page -
                        1
                    )
                  }
                  className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Previous
                </button>

                <span className="flex items-center px-3 text-sm text-slate-600">
                  Page{" "}
                  {
                    contactPagination.current_page
                  }{" "}
                  of{" "}
                  {contactPagination.last_page}
                </span>

                <button
                  type="button"
                  disabled={
                    isContactLoading ||
                    contactPagination.current_page >=
                      contactPagination.last_page
                  }
                  onClick={() =>
                    void loadSupplierContacts(
                      selectedSupplier,
                      contactPagination.current_page +
                        1
                    )
                  }
                  className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Next
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {isContactFormOpen &&
        selectedSupplier && (
          <SupplierContactForm
            key={
              editingContact?.id ??
              "new-supplier-contact"
            }
            supplier={selectedSupplier}
            contact={editingContact}
            isSaving={isContactSaving}
            errors={contactFormErrors}
            onCancel={closeContactForm}
            onSubmit={handleContactSave}
          />
        )}
    </div>
  );
}