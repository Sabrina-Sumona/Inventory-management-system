"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";

import { CustomerContactForm } from "@/components/customers/CustomerContactForm";
import { CustomerFinancialSettingPanel } from "@/components/customers/CustomerFinancialSettingPanel";
import { CustomerForm } from "@/components/customers/CustomerForm";
import { useAuth } from "@/contexts/AuthContext";
import { branchService } from "@/services/branchService";
import { customerService } from "@/services/customerService";
import type { ApiErrorResponse } from "@/types/auth";
import type { Branch } from "@/types/branch";
import type {
  Customer,
  CustomerContact,
  CustomerContactPagination,
  CustomerContactPayload,
  CustomerContactQuery,
  CustomerContactType,
  CustomerOpeningBalanceType,
  CustomerPagination,
  CustomerPayload,
  CustomerQuery,
  CustomerType,
} from "@/types/customer";

const emptyCustomerPagination: CustomerPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
};

const emptyContactPagination: CustomerContactPagination = {
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

function formatLabel(
  value: string
): string {
  return value
    .split("_")
    .map(
      (word) =>
        word.charAt(0).toUpperCase() +
        word.slice(1)
    )
    .join(" ");
}

export default function CustomersPage() {
  const { user } = useAuth();

  const [customers, setCustomers] =
    useState<Customer[]>([]);

  const [branches, setBranches] =
    useState<Branch[]>([]);

  const [
    customerPagination,
    setCustomerPagination,
  ] = useState<CustomerPagination>(
    emptyCustomerPagination
  );

  const [search, setSearch] = useState("");

  const [activeFilter, setActiveFilter] =
    useState<
      "all" | "active" | "inactive"
    >("all");

  const [
    customerTypeFilter,
    setCustomerTypeFilter,
  ] = useState<
    "all" | CustomerType
  >("all");

  const [
    balanceTypeFilter,
    setBalanceTypeFilter,
  ] = useState<
    "all" | CustomerOpeningBalanceType
  >("all");

  const [branchFilter, setBranchFilter] =
    useState<string>("all");

  const [
    editingCustomer,
    setEditingCustomer,
  ] = useState<Customer | null>(null);

  const [
    isCustomerFormOpen,
    setIsCustomerFormOpen,
  ] = useState(false);

  const [
    selectedCustomer,
    setSelectedCustomer,
  ] = useState<Customer | null>(null);

  const [
    selectedFinancialCustomer,
    setSelectedFinancialCustomer,
  ] = useState<Customer | null>(null);

  const [
    customerContacts,
    setCustomerContacts,
  ] = useState<CustomerContact[]>([]);

  const [
    contactPagination,
    setContactPagination,
  ] = useState<CustomerContactPagination>(
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
    "all" | CustomerContactType
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
  ] = useState<CustomerContact | null>(
    null
  );

  const [
    isContactFormOpen,
    setIsContactFormOpen,
  ] = useState(false);

  const [
    isCustomerLoading,
    setIsCustomerLoading,
  ] = useState(true);

  const [
    isContactLoading,
    setIsContactLoading,
  ] = useState(false);

  const [
    isCustomerSaving,
    setIsCustomerSaving,
  ] = useState(false);

  const [
    isContactSaving,
    setIsContactSaving,
  ] = useState(false);

  const [
    updatingCustomerId,
    setUpdatingCustomerId,
  ] = useState<number | null>(null);

  const [
    deletingCustomerId,
    setDeletingCustomerId,
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
    customerFormErrors,
    setCustomerFormErrors,
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

  const canCreateCustomer =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer.create"
    ) ?? false;

  const canUpdateCustomer =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer.update"
    ) ?? false;

  const canDeleteCustomer =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer.delete"
    ) ?? false;

  const canViewContacts =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer-contact.view"
    ) ?? false;

  const canCreateContact =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer-contact.create"
    ) ?? false;

  const canUpdateContact =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer-contact.update"
    ) ?? false;

  const canDeleteContact =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer-contact.delete"
    ) ?? false;

  const canViewFinancialSettings =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer-financial-setting.view"
    ) ?? false;

  const canCreateFinancialSettings =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer-financial-setting.create"
    ) ?? false;

  const canUpdateFinancialSettings =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer-financial-setting.update"
    ) ?? false;

  const canDeleteFinancialSettings =
    user?.permissions.some(
      (permission) =>
        permission.code ===
        "customer-financial-setting.delete"
    ) ?? false;

  async function loadCustomers(
    page = 1
  ): Promise<void> {
    setIsCustomerLoading(true);
    setErrorMessage(null);

    const selectedBranchId =
      branchFilter === "all"
        ? undefined
        : Number(branchFilter);

    const query: CustomerQuery = {
      search:
        search.trim() || undefined,

      branch_id:
        selectedBranchId &&
        Number.isInteger(selectedBranchId)
          ? selectedBranchId
          : undefined,

      customer_type:
        customerTypeFilter === "all"
          ? undefined
          : customerTypeFilter,

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
        await customerService.getCustomers(
          query
        );

      setCustomers(data.customers);

      setCustomerPagination(
        data.pagination
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load customers."
        )
      );
    } finally {
      setIsCustomerLoading(false);
    }
  }

  async function loadCustomerContacts(
    customer: Customer,
    page = 1
  ): Promise<void> {
    setIsContactLoading(true);
    setErrorMessage(null);

    const query: CustomerContactQuery = {
      customer_id: customer.id,

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
        await customerService.getCustomerContacts(
          query
        );

      setCustomerContacts(
        data.customer_contacts
      );

      setContactPagination(
        data.pagination
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load customer contacts."
        )
      );
    } finally {
      setIsContactLoading(false);
    }
  }

  useEffect(() => {
    let isMounted = true;

    customerService
      .getCustomers({
        page: 1,
        per_page: 10,
        sort_by: "name",
        sort_direction: "asc",
      })
      .then((data) => {
        if (!isMounted) {
          return;
        }

        setCustomers(data.customers);

        setCustomerPagination(
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
            "Unable to load customers."
          )
        );
      })
      .finally(() => {
        if (isMounted) {
          setIsCustomerLoading(false);
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

  async function handleCustomerSearch(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    await loadCustomers(1);
  }

  async function handleContactSearch(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    if (!selectedCustomer) {
      return;
    }

    await loadCustomerContacts(
      selectedCustomer,
      1
    );
  }

  function openCreateCustomerForm(): void {
    setEditingCustomer(null);
    setCustomerFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsCustomerFormOpen(true);
  }

  function openEditCustomerForm(
    customer: Customer
  ): void {
    setEditingCustomer(customer);
    setCustomerFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setIsCustomerFormOpen(true);
  }

  function closeCustomerForm(): void {
    if (isCustomerSaving) {
      return;
    }

    setIsCustomerFormOpen(false);
    setEditingCustomer(null);
    setCustomerFormErrors({});
  }

  function openFinancialSettingsPanel(
    customer: Customer
  ): void {
    setSelectedFinancialCustomer(
      customer
    );

    setErrorMessage(null);
    setSuccessMessage(null);
  }

  function closeFinancialSettingsPanel(): void {
    setSelectedFinancialCustomer(null);
  }

  async function openContactsPanel(
    customer: Customer
  ): Promise<void> {
    setSelectedCustomer(customer);
    setCustomerContacts([]);

    setContactPagination(
      emptyContactPagination
    );

    setContactSearch("");
    setContactTypeFilter("all");
    setContactStatusFilter("all");
    setPrimaryContactFilter("all");
    setErrorMessage(null);
    setSuccessMessage(null);

    await loadCustomerContacts(
      customer,
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

    setSelectedCustomer(null);
    setCustomerContacts([]);

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
    contact: CustomerContact
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

  async function handleCustomerSave(
    payload: CustomerPayload
  ): Promise<void> {
    setIsCustomerSaving(true);
    setCustomerFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      if (editingCustomer) {
        await customerService.updateCustomer(
          editingCustomer.id,
          payload
        );

        setSuccessMessage(
          "Customer updated successfully."
        );
      } else {
        await customerService.createCustomer(
          payload
        );

        setSuccessMessage(
          "Customer created successfully."
        );
      }

      setIsCustomerFormOpen(false);
      setEditingCustomer(null);

      await loadCustomers(
        customerPagination.current_page
      );
    } catch (error) {
      setCustomerFormErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to save the customer."
        )
      );
    } finally {
      setIsCustomerSaving(false);
    }
  }

  async function handleContactSave(
    payload: CustomerContactPayload
  ): Promise<void> {
    if (!selectedCustomer) {
      return;
    }

    setIsContactSaving(true);
    setContactFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      if (editingContact) {
        await customerService.updateCustomerContact(
          editingContact.id,
          {
            name: payload.name,

            designation:
              payload.designation,

            department:
              payload.department,

            contact_type:
              payload.contact_type,

            email:
              payload.email,

            phone:
              payload.phone,

            alternate_phone:
              payload.alternate_phone,

            is_primary:
              payload.is_primary,

            is_active:
              payload.is_active,

            notes:
              payload.notes,
          }
        );

        setSuccessMessage(
          "Customer contact updated successfully."
        );
      } else {
        await customerService.createCustomerContact(
          payload
        );

        setSuccessMessage(
          "Customer contact created successfully."
        );
      }

      setIsContactFormOpen(false);
      setEditingContact(null);

      await loadCustomerContacts(
        selectedCustomer,
        contactPagination.current_page
      );
    } catch (error) {
      setContactFormErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to save the customer contact."
        )
      );
    } finally {
      setIsContactSaving(false);
    }
  }

  async function handleCustomerStatusChange(
    customer: Customer
  ): Promise<void> {
    setUpdatingCustomerId(customer.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await customerService.updateCustomer(
        customer.id,
        {
          is_active:
            !customer.is_active,
        }
      );

      setSuccessMessage(
        customer.is_active
          ? "Customer deactivated successfully."
          : "Customer activated successfully."
      );

      await loadCustomers(
        customerPagination.current_page
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to update customer status."
        )
      );
    } finally {
      setUpdatingCustomerId(null);
    }
  }

  async function handleContactStatusChange(
    contact: CustomerContact
  ): Promise<void> {
    if (!selectedCustomer) {
      return;
    }

    setUpdatingContactId(contact.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await customerService.updateCustomerContact(
        contact.id,
        {
          is_active:
            !contact.is_active,
        }
      );

      setSuccessMessage(
        contact.is_active
          ? "Customer contact deactivated successfully."
          : "Customer contact activated successfully."
      );

      await loadCustomerContacts(
        selectedCustomer,
        contactPagination.current_page
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to update customer contact status."
        )
      );
    } finally {
      setUpdatingContactId(null);
    }
  }

  async function handleMakePrimary(
    contact: CustomerContact
  ): Promise<void> {
    if (
      !selectedCustomer ||
      contact.is_primary
    ) {
      return;
    }

    setUpdatingContactId(contact.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await customerService.updateCustomerContact(
        contact.id,
        {
          is_primary: true,
        }
      );

      setSuccessMessage(
        "Primary customer contact updated successfully."
      );

      await loadCustomerContacts(
        selectedCustomer,
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

  async function handleCustomerDelete(
    customer: Customer
  ): Promise<void> {
    const confirmed = window.confirm(
      `Delete "${customer.name}"? This will soft-delete the customer.`
    );

    if (!confirmed) {
      return;
    }

    setDeletingCustomerId(customer.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await customerService.deleteCustomer(
        customer.id
      );

      setSuccessMessage(
        "Customer deleted successfully."
      );

      const nextPage =
        customers.length === 1 &&
        customerPagination.current_page >
          1
          ? customerPagination.current_page -
            1
          : customerPagination.current_page;

      await loadCustomers(nextPage);
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to delete the customer."
        )
      );
    } finally {
      setDeletingCustomerId(null);
    }
  }

  async function handleContactDelete(
    contact: CustomerContact
  ): Promise<void> {
    if (!selectedCustomer) {
      return;
    }

    const confirmed = window.confirm(
      `Delete "${contact.name}"? This will soft-delete the customer contact.`
    );

    if (!confirmed) {
      return;
    }

    setDeletingContactId(contact.id);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      await customerService.deleteCustomerContact(
        contact.id
      );

      setSuccessMessage(
        "Customer contact deleted successfully."
      );

      const nextPage =
        customerContacts.length === 1 &&
        contactPagination.current_page > 1
          ? contactPagination.current_page -
            1
          : contactPagination.current_page;

      await loadCustomerContacts(
        selectedCustomer,
        nextPage
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to delete the customer contact."
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
            Customer Management
          </h2>

          <p className="mt-2 text-sm text-slate-500">
            Manage customer profiles,
            contacts, branch access,
            billing and shipping details,
            credit limits, financial settings,
            balances, and operational status.
          </p>
        </div>

        {canCreateCustomer && (
          <button
            type="button"
            onClick={openCreateCustomerForm}
            className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
          >
            Add customer
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
          onSubmit={handleCustomerSearch}
          className="grid gap-4 border-b border-slate-200 p-5 2xl:grid-cols-[1fr_170px_170px_180px_190px_auto]"
        >
          <input
            type="search"
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
            placeholder="Search customer name, code, email or phone"
            className="h-11 rounded-lg border border-slate-300 bg-white px-4 text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
          />

          <select
            value={customerTypeFilter}
            onChange={(event) =>
              setCustomerTypeFilter(
                event.target.value as
                  | "all"
                  | CustomerType
              )
            }
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
          >
            <option value="all">
              All customer types
            </option>

            <option value="retail">
              Retail
            </option>

            <option value="wholesale">
              Wholesale
            </option>

            <option value="corporate">
              Corporate
            </option>

            <option value="government">
              Government
            </option>

            <option value="dealer">
              Dealer
            </option>

            <option value="distributor">
              Distributor
            </option>

            <option value="other">
              Other
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
                  | CustomerOpeningBalanceType
              )
            }
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
          >
            <option value="all">
              All balance types
            </option>

            <option value="receivable">
              Receivable
            </option>

            <option value="payable">
              Payable
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
            disabled={isCustomerLoading}
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
                  "Customer",
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
              {isCustomerLoading ? (
                <tr>
                  <td
                    colSpan={7}
                    className="px-5 py-12 text-center text-sm text-slate-500"
                  >
                    Loading customers...
                  </td>
                </tr>
              ) : customers.length === 0 ? (
                <tr>
                  <td
                    colSpan={7}
                    className="px-5 py-12 text-center text-sm text-slate-500"
                  >
                    No customers found.
                  </td>
                </tr>
              ) : (
                customers.map((customer) => (
                  <tr
                    key={customer.id}
                    className="align-top"
                  >
                    <td className="px-5 py-4">
                      <p className="font-semibold text-slate-900">
                        {customer.name}
                      </p>

                      <p className="mt-1 text-xs font-medium text-slate-500">
                        {customer.code}
                      </p>

                      <span className="mt-2 inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800">
                        {formatLabel(
                          customer.customer_type
                        )}
                      </span>

                      {customer.business_name && (
                        <p className="mt-2 max-w-xs text-xs text-slate-500">
                          {
                            customer.business_name
                          }
                        </p>
                      )}
                    </td>

                    <td className="px-5 py-4">
                      {customer.branch ? (
                        <>
                          <p className="text-sm font-medium text-slate-700">
                            {
                              customer.branch
                                .name
                            }
                          </p>

                          <p className="mt-1 text-xs text-slate-500">
                            {
                              customer.branch
                                .code
                            }
                          </p>

                          {customer.branch
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
                        {customer.email ?? "—"}
                      </p>

                      <p className="mt-1">
                        {customer.phone ?? "—"}
                      </p>

                      {customer.alternate_phone && (
                        <p className="mt-1 text-xs text-slate-500">
                          Alt:{" "}
                          {
                            customer.alternate_phone
                          }
                        </p>
                      )}
                    </td>

                    <td className="px-5 py-4">
                      <p className="text-sm font-semibold text-slate-700">
                        {
                          customer.payment_term_days
                        }{" "}
                        days
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        Credit limit:{" "}
                        {formatMoney(
                          customer.credit_limit
                        )}
                      </p>
                    </td>

                    <td className="px-5 py-4">
                      <p className="text-sm font-semibold text-slate-700">
                        {formatMoney(
                          customer.opening_balance
                        )}
                      </p>

                      <span
                        className={`mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                          customer.opening_balance_type ===
                          "receivable"
                            ? "bg-blue-100 text-blue-800"
                            : "bg-amber-100 text-amber-800"
                        }`}
                      >
                        {customer.opening_balance_type ===
                        "receivable"
                          ? "Receivable"
                          : "Payable"}
                      </span>
                    </td>

                    <td className="px-5 py-4">
                      <span
                        className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                          customer.is_active
                            ? "bg-emerald-100 text-emerald-800"
                            : "bg-red-100 text-red-800"
                        }`}
                      >
                        {customer.is_active
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
                                customer
                              )
                            }
                            className="rounded-md border border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                          >
                            Contacts
                          </button>
                        )}

                        {canViewFinancialSettings && (
                          <button
                            type="button"
                            onClick={() =>
                              openFinancialSettingsPanel(
                                customer
                              )
                            }
                            className="rounded-md border border-violet-300 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-50"
                          >
                            Financial
                          </button>
                        )}

                        {canUpdateCustomer && (
                          <>
                            <button
                              type="button"
                              onClick={() =>
                                openEditCustomerForm(
                                  customer
                                )
                              }
                              disabled={
                                updatingCustomerId ===
                                  customer.id ||
                                deletingCustomerId ===
                                  customer.id
                              }
                              className="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                              Edit
                            </button>

                            <button
                              type="button"
                              onClick={() =>
                                void handleCustomerStatusChange(
                                  customer
                                )
                              }
                              disabled={
                                updatingCustomerId ===
                                  customer.id ||
                                deletingCustomerId ===
                                  customer.id
                              }
                              className="rounded-md border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                              {updatingCustomerId ===
                              customer.id
                                ? "Updating..."
                                : customer.is_active
                                  ? "Deactivate"
                                  : "Activate"}
                            </button>
                          </>
                        )}

                        {canDeleteCustomer && (
                          <button
                            type="button"
                            onClick={() =>
                              void handleCustomerDelete(
                                customer
                              )
                            }
                            disabled={
                              deletingCustomerId ===
                                customer.id ||
                              updatingCustomerId ===
                                customer.id
                            }
                            className="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                          >
                            {deletingCustomerId ===
                            customer.id
                              ? "Deleting..."
                              : "Delete"}
                          </button>
                        )}

                        {!canViewContacts &&
                          !canViewFinancialSettings &&
                          !canUpdateCustomer &&
                          !canDeleteCustomer && (
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
            {customerPagination.from ?? 0}–
            {customerPagination.to ?? 0} of{" "}
            {customerPagination.total} customers
          </p>

          <div className="flex gap-2">
            <button
              type="button"
              disabled={
                isCustomerLoading ||
                customerPagination.current_page <=
                  1
              }
              onClick={() =>
                void loadCustomers(
                  customerPagination.current_page -
                    1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Previous
            </button>

            <span className="flex items-center px-3 text-sm text-slate-600">
              Page{" "}
              {customerPagination.current_page}{" "}
              of{" "}
              {customerPagination.last_page}
            </span>

            <button
              type="button"
              disabled={
                isCustomerLoading ||
                customerPagination.current_page >=
                  customerPagination.last_page
              }
              onClick={() =>
                void loadCustomers(
                  customerPagination.current_page +
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

      {isCustomerFormOpen && (
        <CustomerForm
          key={
            editingCustomer?.id ??
            "new-customer"
          }
          customer={editingCustomer}
          branches={branches}
          isSaving={isCustomerSaving}
          errors={customerFormErrors}
          onCancel={closeCustomerForm}
          onSubmit={handleCustomerSave}
        />
      )}

      {selectedCustomer && (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/60 p-4">
          <div className="max-h-[94vh] w-full max-w-7xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div className="flex flex-col justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center">
              <div>
                <h2 className="text-xl font-bold text-slate-950">
                  Customer Contacts
                </h2>

                <p className="mt-1 text-sm text-slate-500">
                  {selectedCustomer.name} ·{" "}
                  {selectedCustomer.code}
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
                      | CustomerContactType
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

                <option value="management">
                  Management
                </option>

                <option value="support">
                  Support
                </option>

                <option value="purchase">
                  Purchase
                </option>

                <option value="other">
                  Other
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
                        Loading customer contacts...
                      </td>
                    </tr>
                  ) : customerContacts.length ===
                    0 ? (
                    <tr>
                      <td
                        colSpan={6}
                        className="px-5 py-12 text-center text-sm text-slate-500"
                      >
                        No customer contacts found.
                      </td>
                    </tr>
                  ) : (
                    customerContacts.map(
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
                              {formatLabel(
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
                    void loadCustomerContacts(
                      selectedCustomer,
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
                    void loadCustomerContacts(
                      selectedCustomer,
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

      {selectedFinancialCustomer && (
        <CustomerFinancialSettingPanel
          key={
            selectedFinancialCustomer.id
          }
          customer={
            selectedFinancialCustomer
          }
          canCreate={
            canCreateFinancialSettings
          }
          canUpdate={
            canUpdateFinancialSettings
          }
          canDelete={
            canDeleteFinancialSettings
          }
          onClose={
            closeFinancialSettingsPanel
          }
        />
      )}

      {isContactFormOpen &&
        selectedCustomer && (
          <CustomerContactForm
            key={
              editingContact?.id ??
              "new-customer-contact"
            }
            customer={selectedCustomer}
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