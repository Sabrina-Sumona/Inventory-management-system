"use client";

import {
  type FormEvent,
  useMemo,
  useState,
} from "react";

import type { Branch } from "@/types/branch";
import type {
  Customer,
  CustomerOpeningBalanceType,
  CustomerPayload,
  CustomerType,
} from "@/types/customer";

interface CustomerFormValues {
  branchId: string;

  name: string;
  code: string;
  businessName: string;
  customerType: CustomerType;

  email: string;
  phone: string;
  alternatePhone: string;
  website: string;

  taxIdentificationNumber: string;
  tradeLicenseNumber: string;

  billingAddressLine1: string;
  billingAddressLine2: string;
  billingCity: string;
  billingDistrict: string;
  billingPostalCode: string;
  billingCountry: string;

  shippingAddressLine1: string;
  shippingAddressLine2: string;
  shippingCity: string;
  shippingDistrict: string;
  shippingPostalCode: string;
  shippingCountry: string;

  paymentTermDays: string;
  creditLimit: string;
  openingBalance: string;
  openingBalanceType: CustomerOpeningBalanceType;

  notes: string;
  isActive: boolean;
}

interface CustomerFormProps {
  customer: Customer | null;
  branches: Branch[];
  isSaving: boolean;
  errors: Record<string, string>;
  onCancel: () => void;
  onSubmit: (
    payload: CustomerPayload
  ) => Promise<void>;
}

const emptyForm: CustomerFormValues = {
  branchId: "",

  name: "",
  code: "",
  businessName: "",
  customerType: "retail",

  email: "",
  phone: "",
  alternatePhone: "",
  website: "",

  taxIdentificationNumber: "",
  tradeLicenseNumber: "",

  billingAddressLine1: "",
  billingAddressLine2: "",
  billingCity: "",
  billingDistrict: "",
  billingPostalCode: "",
  billingCountry: "Bangladesh",

  shippingAddressLine1: "",
  shippingAddressLine2: "",
  shippingCity: "",
  shippingDistrict: "",
  shippingPostalCode: "",
  shippingCountry: "Bangladesh",

  paymentTermDays: "0",
  creditLimit: "0",
  openingBalance: "0",
  openingBalanceType: "receivable",

  notes: "",
  isActive: true,
};

const inputClassName =
  "h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500";

const selectClassName =
  "h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500";

const textareaClassName =
  "w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500";

function nullableValue(
  value: string
): string | null {
  const trimmedValue = value.trim();

  return trimmedValue === ""
    ? null
    : trimmedValue;
}

function safeNumber(
  value: string,
  fallback = 0
): number {
  const parsedValue = Number(value);

  return Number.isFinite(parsedValue)
    ? parsedValue
    : fallback;
}

function customerToForm(
  customer: Customer | null
): CustomerFormValues {
  if (!customer) {
    return {
      ...emptyForm,
    };
  }

  return {
    branchId:
      customer.branch_id === null
        ? ""
        : String(customer.branch_id),

    name: customer.name,
    code: customer.code,

    businessName:
      customer.business_name ?? "",

    customerType:
      customer.customer_type,

    email: customer.email ?? "",
    phone: customer.phone ?? "",

    alternatePhone:
      customer.alternate_phone ?? "",

    website:
      customer.website ?? "",

    taxIdentificationNumber:
      customer.tax_identification_number ??
      "",

    tradeLicenseNumber:
      customer.trade_license_number ?? "",

    billingAddressLine1:
      customer.billing_address_line_1 ?? "",

    billingAddressLine2:
      customer.billing_address_line_2 ?? "",

    billingCity:
      customer.billing_city ?? "",

    billingDistrict:
      customer.billing_district ?? "",

    billingPostalCode:
      customer.billing_postal_code ?? "",

    billingCountry:
      customer.billing_country ||
      "Bangladesh",

    shippingAddressLine1:
      customer.shipping_address_line_1 ??
      "",

    shippingAddressLine2:
      customer.shipping_address_line_2 ??
      "",

    shippingCity:
      customer.shipping_city ?? "",

    shippingDistrict:
      customer.shipping_district ?? "",

    shippingPostalCode:
      customer.shipping_postal_code ?? "",

    shippingCountry:
      customer.shipping_country ||
      "Bangladesh",

    paymentTermDays: String(
      customer.payment_term_days
    ),

    creditLimit: String(
      customer.credit_limit
    ),

    openingBalance: String(
      customer.opening_balance
    ),

    openingBalanceType:
      customer.opening_balance_type,

    notes:
      customer.notes ?? "",

    isActive:
      customer.is_active,
  };
}

export function CustomerForm({
  customer,
  branches,
  isSaving,
  errors,
  onCancel,
  onSubmit,
}: CustomerFormProps) {
  const [form, setForm] =
    useState<CustomerFormValues>(
      customerToForm(customer)
    );

  const [
    useBillingAsShipping,
    setUseBillingAsShipping,
  ] = useState(false);

  const availableBranches =
    useMemo<Branch[]>(() => {
      if (
        !customer ||
        customer.branch_id === null ||
        customer.branch === null
      ) {
        return branches;
      }

      const currentBranchIsAvailable =
        branches.some(
          (branch) =>
            branch.id === customer.branch_id
        );

      if (currentBranchIsAvailable) {
        return branches;
      }

      const currentBranch: Branch = {
        id: customer.branch.id,
        company_id: customer.company_id,
        name: customer.branch.name,
        code: customer.branch.code,
        email: null,
        phone: null,
        address: null,
        city: customer.branch.city,
        district:
          customer.branch.district,
        postal_code: null,

        is_head_office:
          customer.branch.is_head_office,

        is_active:
          customer.branch.is_active,

        company:
          customer.company ?? {
            id: customer.company_id,
            name: "Current company",
            code: "",
          },

        warehouses_count: 0,
        users_count: 0,
        created_at: null,
        updated_at: null,
        deleted_at: null,
      };

      return [
        currentBranch,
        ...branches,
      ];
    }, [branches, customer]);

  function updateField<
    Key extends keyof CustomerFormValues
  >(
    field: Key,
    value: CustomerFormValues[Key]
  ): void {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  }

  function handleShippingAddressSync(
    checked: boolean
  ): void {
    setUseBillingAsShipping(checked);

    if (!checked) {
      return;
    }

    setForm((current) => ({
      ...current,

      shippingAddressLine1:
        current.billingAddressLine1,

      shippingAddressLine2:
        current.billingAddressLine2,

      shippingCity:
        current.billingCity,

      shippingDistrict:
        current.billingDistrict,

      shippingPostalCode:
        current.billingPostalCode,

      shippingCountry:
        current.billingCountry,
    }));
  }

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    const branchId = Number(
      form.branchId
    );

    if (
      form.branchId === "" ||
      !Number.isInteger(branchId) ||
      branchId <= 0
    ) {
      return;
    }

    await onSubmit({
      branch_id: branchId,

      name:
        form.name.trim(),

      code:
        form.code
          .trim()
          .toUpperCase()
          .replace(/\s+/g, "-"),

      business_name:
        nullableValue(
          form.businessName
        ),

      customer_type:
        form.customerType,

      email:
        nullableValue(form.email),

      phone:
        nullableValue(form.phone),

      alternate_phone:
        nullableValue(
          form.alternatePhone
        ),

      website:
        nullableValue(form.website),

      tax_identification_number:
        nullableValue(
          form.taxIdentificationNumber
        ),

      trade_license_number:
        nullableValue(
          form.tradeLicenseNumber
        ),

      billing_address_line_1:
        nullableValue(
          form.billingAddressLine1
        ),

      billing_address_line_2:
        nullableValue(
          form.billingAddressLine2
        ),

      billing_city:
        nullableValue(
          form.billingCity
        ),

      billing_district:
        nullableValue(
          form.billingDistrict
        ),

      billing_postal_code:
        nullableValue(
          form.billingPostalCode
        ),

      billing_country:
        form.billingCountry.trim() ||
        "Bangladesh",

      shipping_address_line_1:
        nullableValue(
          form.shippingAddressLine1
        ),

      shipping_address_line_2:
        nullableValue(
          form.shippingAddressLine2
        ),

      shipping_city:
        nullableValue(
          form.shippingCity
        ),

      shipping_district:
        nullableValue(
          form.shippingDistrict
        ),

      shipping_postal_code:
        nullableValue(
          form.shippingPostalCode
        ),

      shipping_country:
        form.shippingCountry.trim() ||
        "Bangladesh",

      payment_term_days:
        Math.max(
          0,
          Math.trunc(
            safeNumber(
              form.paymentTermDays
            )
          )
        ),

      credit_limit:
        Math.max(
          0,
          safeNumber(
            form.creditLimit
          )
        ),

      opening_balance:
        Math.max(
          0,
          safeNumber(
            form.openingBalance
          )
        ),

      opening_balance_type:
        form.openingBalanceType,

      notes:
        nullableValue(form.notes),

      is_active:
        form.isActive,
    });
  }

  return (
    <div className="fixed inset-0 z-[70] overflow-y-auto bg-slate-950/60 px-4 py-8">
      <div className="mx-auto w-full max-w-6xl rounded-2xl bg-white shadow-xl">
        <header className="flex items-start justify-between border-b border-slate-200 px-6 py-5">
          <div>
            <h2 className="text-xl font-bold text-slate-950">
              {customer
                ? "Edit customer"
                : "Create customer"}
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              {customer
                ? "Update customer identity, contact, address, and financial information."
                : "Add a new customer under an accessible branch."}
            </p>
          </div>

          <button
            type="button"
            onClick={onCancel}
            disabled={isSaving}
            aria-label="Close customer form"
            className="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
          >
            ✕
          </button>
        </header>

        <form
          onSubmit={handleSubmit}
          className="p-6"
        >
          <div className="space-y-8">
            <section>
              <h3 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                Basic information
              </h3>

              <div className="grid gap-5 md:grid-cols-2">
                <div className="md:col-span-2">
                  <label
                    htmlFor="customer-branch"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Branch
                    <span className="ml-1 text-red-600">
                      *
                    </span>
                  </label>

                  <select
                    id="customer-branch"
                    required
                    value={form.branchId}
                    onChange={(event) =>
                      updateField(
                        "branchId",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={selectClassName}
                  >
                    <option
                      value=""
                      disabled
                    >
                      Select a branch
                    </option>

                    {availableBranches.map(
                      (branch) => (
                        <option
                          key={branch.id}
                          value={branch.id}
                        >
                          {branch.name} (
                          {branch.code})
                        </option>
                      )
                    )}
                  </select>

                  <p className="mt-2 text-xs text-slate-500">
                    Select the branch responsible
                    for this customer.
                  </p>

                  {availableBranches.length ===
                    0 && (
                    <p className="mt-2 text-sm text-amber-700">
                      No accessible active branches
                      are available. Create or assign
                      a branch before adding a
                      customer.
                    </p>
                  )}

                  {errors.branch_id && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.branch_id}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-name"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Customer name
                    <span className="ml-1 text-red-600">
                      *
                    </span>
                  </label>

                  <input
                    id="customer-name"
                    required
                    maxLength={150}
                    value={form.name}
                    onChange={(event) =>
                      updateField(
                        "name",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.name && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.name}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-code"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Customer code
                    <span className="ml-1 text-red-600">
                      *
                    </span>
                  </label>

                  <input
                    id="customer-code"
                    required
                    maxLength={50}
                    placeholder="CUS-001"
                    value={form.code}
                    onChange={(event) =>
                      updateField(
                        "code",
                        event.target.value.toUpperCase()
                      )
                    }
                    disabled={isSaving}
                    className={`${inputClassName} uppercase`}
                  />

                  {errors.code && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.code}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-type"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Customer type
                  </label>

                  <select
                    id="customer-type"
                    value={form.customerType}
                    onChange={(event) =>
                      updateField(
                        "customerType",
                        event.target
                          .value as CustomerType
                      )
                    }
                    disabled={isSaving}
                    className={selectClassName}
                  >
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

                  {errors.customer_type && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.customer_type}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-business-name"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Registered business name
                  </label>

                  <input
                    id="customer-business-name"
                    maxLength={150}
                    value={form.businessName}
                    onChange={(event) =>
                      updateField(
                        "businessName",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.business_name && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.business_name}
                    </p>
                  )}
                </div>
              </div>
            </section>

            <section>
              <h3 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                Contact information
              </h3>

              <div className="grid gap-5 md:grid-cols-2">
                <div>
                  <label
                    htmlFor="customer-email"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Email
                  </label>

                  <input
                    id="customer-email"
                    type="email"
                    maxLength={150}
                    value={form.email}
                    onChange={(event) =>
                      updateField(
                        "email",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.email && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.email}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-phone"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Phone
                  </label>

                  <input
                    id="customer-phone"
                    maxLength={30}
                    value={form.phone}
                    onChange={(event) =>
                      updateField(
                        "phone",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.phone && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.phone}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-alternate-phone"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Alternate phone
                  </label>

                  <input
                    id="customer-alternate-phone"
                    maxLength={30}
                    value={form.alternatePhone}
                    onChange={(event) =>
                      updateField(
                        "alternatePhone",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.alternate_phone && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.alternate_phone}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-website"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Website
                  </label>

                  <input
                    id="customer-website"
                    type="url"
                    maxLength={255}
                    placeholder="https://example.com"
                    value={form.website}
                    onChange={(event) =>
                      updateField(
                        "website",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.website && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.website}
                    </p>
                  )}
                </div>
              </div>
            </section>

            <section>
              <h3 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                Business registration
              </h3>

              <div className="grid gap-5 md:grid-cols-2">
                <div>
                  <label
                    htmlFor="customer-tax-number"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Tax identification number
                  </label>

                  <input
                    id="customer-tax-number"
                    maxLength={100}
                    value={
                      form.taxIdentificationNumber
                    }
                    onChange={(event) =>
                      updateField(
                        "taxIdentificationNumber",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.tax_identification_number && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.tax_identification_number
                      }
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-trade-license"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Trade license number
                  </label>

                  <input
                    id="customer-trade-license"
                    maxLength={100}
                    value={
                      form.tradeLicenseNumber
                    }
                    onChange={(event) =>
                      updateField(
                        "tradeLicenseNumber",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.trade_license_number && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.trade_license_number
                      }
                    </p>
                  )}
                </div>
              </div>
            </section>

            <section>
              <h3 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                Billing address
              </h3>

              <div className="grid gap-5 md:grid-cols-2">
                <div className="md:col-span-2">
                  <label
                    htmlFor="customer-billing-address-line-1"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Address line 1
                  </label>

                  <input
                    id="customer-billing-address-line-1"
                    maxLength={255}
                    value={
                      form.billingAddressLine1
                    }
                    onChange={(event) =>
                      updateField(
                        "billingAddressLine1",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.billing_address_line_1 && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.billing_address_line_1
                      }
                    </p>
                  )}
                </div>

                <div className="md:col-span-2">
                  <label
                    htmlFor="customer-billing-address-line-2"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Address line 2
                  </label>

                  <input
                    id="customer-billing-address-line-2"
                    maxLength={255}
                    value={
                      form.billingAddressLine2
                    }
                    onChange={(event) =>
                      updateField(
                        "billingAddressLine2",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.billing_address_line_2 && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.billing_address_line_2
                      }
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-billing-city"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    City
                  </label>

                  <input
                    id="customer-billing-city"
                    maxLength={100}
                    value={form.billingCity}
                    onChange={(event) =>
                      updateField(
                        "billingCity",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.billing_city && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.billing_city}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-billing-district"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    District
                  </label>

                  <input
                    id="customer-billing-district"
                    maxLength={100}
                    value={
                      form.billingDistrict
                    }
                    onChange={(event) =>
                      updateField(
                        "billingDistrict",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.billing_district && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.billing_district}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-billing-postal-code"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Postal code
                  </label>

                  <input
                    id="customer-billing-postal-code"
                    maxLength={20}
                    value={
                      form.billingPostalCode
                    }
                    onChange={(event) =>
                      updateField(
                        "billingPostalCode",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.billing_postal_code && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.billing_postal_code
                      }
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-billing-country"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Country
                  </label>

                  <input
                    id="customer-billing-country"
                    required
                    maxLength={100}
                    value={
                      form.billingCountry
                    }
                    onChange={(event) =>
                      updateField(
                        "billingCountry",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.billing_country && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.billing_country}
                    </p>
                  )}
                </div>
              </div>
            </section>

            <section>
              <div className="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <h3 className="text-sm font-bold uppercase tracking-wide text-slate-500">
                  Shipping address
                </h3>

                <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                  <input
                    type="checkbox"
                    checked={
                      useBillingAsShipping
                    }
                    onChange={(event) =>
                      handleShippingAddressSync(
                        event.target.checked
                      )
                    }
                    disabled={isSaving}
                    className="h-4 w-4 rounded border-slate-300"
                  />

                  Same as billing address
                </label>
              </div>

              <div className="grid gap-5 md:grid-cols-2">
                <div className="md:col-span-2">
                  <label
                    htmlFor="customer-shipping-address-line-1"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Address line 1
                  </label>

                  <input
                    id="customer-shipping-address-line-1"
                    maxLength={255}
                    value={
                      form.shippingAddressLine1
                    }
                    onChange={(event) =>
                      updateField(
                        "shippingAddressLine1",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.shipping_address_line_1 && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.shipping_address_line_1
                      }
                    </p>
                  )}
                </div>

                <div className="md:col-span-2">
                  <label
                    htmlFor="customer-shipping-address-line-2"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Address line 2
                  </label>

                  <input
                    id="customer-shipping-address-line-2"
                    maxLength={255}
                    value={
                      form.shippingAddressLine2
                    }
                    onChange={(event) =>
                      updateField(
                        "shippingAddressLine2",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.shipping_address_line_2 && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.shipping_address_line_2
                      }
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-shipping-city"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    City
                  </label>

                  <input
                    id="customer-shipping-city"
                    maxLength={100}
                    value={form.shippingCity}
                    onChange={(event) =>
                      updateField(
                        "shippingCity",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.shipping_city && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.shipping_city}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-shipping-district"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    District
                  </label>

                  <input
                    id="customer-shipping-district"
                    maxLength={100}
                    value={
                      form.shippingDistrict
                    }
                    onChange={(event) =>
                      updateField(
                        "shippingDistrict",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.shipping_district && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.shipping_district}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-shipping-postal-code"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Postal code
                  </label>

                  <input
                    id="customer-shipping-postal-code"
                    maxLength={20}
                    value={
                      form.shippingPostalCode
                    }
                    onChange={(event) =>
                      updateField(
                        "shippingPostalCode",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.shipping_postal_code && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.shipping_postal_code
                      }
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-shipping-country"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Country
                  </label>

                  <input
                    id="customer-shipping-country"
                    required
                    maxLength={100}
                    value={
                      form.shippingCountry
                    }
                    onChange={(event) =>
                      updateField(
                        "shippingCountry",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.shipping_country && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.shipping_country}
                    </p>
                  )}
                </div>
              </div>
            </section>

            <section>
              <h3 className="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                Financial information
              </h3>

              <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <div>
                  <label
                    htmlFor="customer-payment-term"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Payment term days
                  </label>

                  <input
                    id="customer-payment-term"
                    type="number"
                    min={0}
                    max={3650}
                    step={1}
                    value={form.paymentTermDays}
                    onChange={(event) =>
                      updateField(
                        "paymentTermDays",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.payment_term_days && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.payment_term_days}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-credit-limit"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Credit limit
                  </label>

                  <input
                    id="customer-credit-limit"
                    type="number"
                    min={0}
                    step="0.01"
                    value={form.creditLimit}
                    onChange={(event) =>
                      updateField(
                        "creditLimit",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.credit_limit && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.credit_limit}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-opening-balance"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Opening balance
                  </label>

                  <input
                    id="customer-opening-balance"
                    type="number"
                    min={0}
                    step="0.01"
                    value={form.openingBalance}
                    onChange={(event) =>
                      updateField(
                        "openingBalance",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.opening_balance && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.opening_balance}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="customer-opening-balance-type"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Balance type
                  </label>

                  <select
                    id="customer-opening-balance-type"
                    value={
                      form.openingBalanceType
                    }
                    onChange={(event) =>
                      updateField(
                        "openingBalanceType",
                        event.target
                          .value as CustomerOpeningBalanceType
                      )
                    }
                    disabled={isSaving}
                    className={selectClassName}
                  >
                    <option value="receivable">
                      Receivable
                    </option>

                    <option value="payable">
                      Payable
                    </option>
                  </select>

                  {errors.opening_balance_type && (
                    <p className="mt-2 text-sm text-red-600">
                      {
                        errors.opening_balance_type
                      }
                    </p>
                  )}
                </div>
              </div>
            </section>

            <section>
              <div className="grid gap-5">
                <div>
                  <label
                    htmlFor="customer-notes"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Notes
                  </label>

                  <textarea
                    id="customer-notes"
                    rows={4}
                    maxLength={5000}
                    value={form.notes}
                    onChange={(event) =>
                      updateField(
                        "notes",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={textareaClassName}
                  />

                  {errors.notes && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.notes}
                    </p>
                  )}
                </div>

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

                  Active customer
                </label>

                {errors.is_active && (
                  <p className="text-sm text-red-600">
                    {errors.is_active}
                  </p>
                )}
              </div>
            </section>
          </div>

          <footer className="mt-8 flex justify-end gap-3 border-t border-slate-200 pt-5">
            <button
              type="button"
              onClick={onCancel}
              disabled={isSaving}
              className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Cancel
            </button>

            <button
              type="submit"
              disabled={
                isSaving ||
                form.branchId === "" ||
                form.name.trim() === "" ||
                form.code.trim() === "" ||
                availableBranches.length === 0
              }
              className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
            >
              {isSaving
                ? "Saving..."
                : customer
                  ? "Save changes"
                  : "Create customer"}
            </button>
          </footer>
        </form>
      </div>
    </div>
  );
}