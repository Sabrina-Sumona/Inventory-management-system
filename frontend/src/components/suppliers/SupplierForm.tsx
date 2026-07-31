"use client";

import {
  type FormEvent,
  useMemo,
  useState,
} from "react";

import type { Branch } from "@/types/branch";
import type {
  Supplier,
  SupplierOpeningBalanceType,
  SupplierPayload,
} from "@/types/supplier";

interface SupplierFormValues {
  branchId: string;

  name: string;
  code: string;
  businessName: string;

  email: string;
  phone: string;
  alternatePhone: string;
  website: string;

  taxIdentificationNumber: string;
  tradeLicenseNumber: string;

  addressLine1: string;
  addressLine2: string;
  city: string;
  district: string;
  postalCode: string;
  country: string;

  paymentTermDays: string;
  creditLimit: string;
  openingBalance: string;
  openingBalanceType: SupplierOpeningBalanceType;

  notes: string;
  isActive: boolean;
}

interface SupplierFormProps {
  supplier: Supplier | null;
  branches: Branch[];
  isSaving: boolean;
  errors: Record<string, string>;
  onCancel: () => void;
  onSubmit: (
    payload: SupplierPayload
  ) => Promise<void>;
}

const emptyForm: SupplierFormValues = {
  branchId: "",

  name: "",
  code: "",
  businessName: "",

  email: "",
  phone: "",
  alternatePhone: "",
  website: "",

  taxIdentificationNumber: "",
  tradeLicenseNumber: "",

  addressLine1: "",
  addressLine2: "",
  city: "",
  district: "",
  postalCode: "",
  country: "Bangladesh",

  paymentTermDays: "0",
  creditLimit: "0",
  openingBalance: "0",
  openingBalanceType: "payable",

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

function supplierToForm(
  supplier: Supplier | null
): SupplierFormValues {
  if (!supplier) {
    return {
      ...emptyForm,
    };
  }

  return {
    branchId:
      supplier.branch_id === null
        ? ""
        : String(supplier.branch_id),

    name: supplier.name,
    code: supplier.code,
    businessName:
      supplier.business_name ?? "",

    email: supplier.email ?? "",
    phone: supplier.phone ?? "",
    alternatePhone:
      supplier.alternate_phone ?? "",
    website: supplier.website ?? "",

    taxIdentificationNumber:
      supplier.tax_identification_number ??
      "",

    tradeLicenseNumber:
      supplier.trade_license_number ?? "",

    addressLine1:
      supplier.address_line_1 ?? "",

    addressLine2:
      supplier.address_line_2 ?? "",

    city: supplier.city ?? "",
    district: supplier.district ?? "",
    postalCode:
      supplier.postal_code ?? "",

    country:
      supplier.country || "Bangladesh",

    paymentTermDays: String(
      supplier.payment_term_days
    ),

    creditLimit: String(
      supplier.credit_limit
    ),

    openingBalance: String(
      supplier.opening_balance
    ),

    openingBalanceType:
      supplier.opening_balance_type,

    notes: supplier.notes ?? "",
    isActive: supplier.is_active,
  };
}

export function SupplierForm({
  supplier,
  branches,
  isSaving,
  errors,
  onCancel,
  onSubmit,
}: SupplierFormProps) {
  const [form, setForm] =
    useState<SupplierFormValues>(
      supplierToForm(supplier)
    );

  const availableBranches =
    useMemo<Branch[]>(() => {
      if (
        !supplier ||
        supplier.branch_id === null ||
        supplier.branch === null
      ) {
        return branches;
      }

      const currentBranchIsAvailable =
        branches.some(
          (branch) =>
            branch.id === supplier.branch_id
        );

      if (currentBranchIsAvailable) {
        return branches;
      }

      const currentBranch: Branch = {
        id: supplier.branch.id,
        company_id: supplier.company_id,
        name: supplier.branch.name,
        code: supplier.branch.code,
        email: null,
        phone: null,
        address: null,
        city: supplier.branch.city,
        district:
          supplier.branch.district,
        postal_code: null,
        is_head_office:
          supplier.branch.is_head_office,
        is_active:
          supplier.branch.is_active,

        company: supplier.company ?? {
          id: supplier.company_id,
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
    }, [branches, supplier]);

  function updateField<
    Key extends keyof SupplierFormValues
  >(
    field: Key,
    value: SupplierFormValues[Key]
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

    const branchId =
      form.branchId === ""
        ? null
        : Number(form.branchId);

    if (
      branchId !== null &&
      (
        !Number.isInteger(branchId) ||
        branchId <= 0
      )
    ) {
      return;
    }

    await onSubmit({
      branch_id: branchId,

      name: form.name.trim(),

      code: form.code
        .trim()
        .toUpperCase()
        .replace(/\s+/g, "-"),

      business_name: nullableValue(
        form.businessName
      ),

      email: nullableValue(form.email),
      phone: nullableValue(form.phone),

      alternate_phone: nullableValue(
        form.alternatePhone
      ),

      website: nullableValue(
        form.website
      ),

      tax_identification_number:
        nullableValue(
          form.taxIdentificationNumber
        ),

      trade_license_number:
        nullableValue(
          form.tradeLicenseNumber
        ),

      address_line_1: nullableValue(
        form.addressLine1
      ),

      address_line_2: nullableValue(
        form.addressLine2
      ),

      city: nullableValue(form.city),

      district: nullableValue(
        form.district
      ),

      postal_code: nullableValue(
        form.postalCode
      ),

      country:
        form.country.trim() ||
        "Bangladesh",

      payment_term_days: Math.max(
        0,
        Math.trunc(
          safeNumber(
            form.paymentTermDays
          )
        )
      ),

      credit_limit: Math.max(
        0,
        safeNumber(form.creditLimit)
      ),

      opening_balance: Math.max(
        0,
        safeNumber(form.openingBalance)
      ),

      opening_balance_type:
        form.openingBalanceType,

      notes: nullableValue(form.notes),
      is_active: form.isActive,
    });
  }

  return (
    <div className="fixed inset-0 z-[70] overflow-y-auto bg-slate-950/60 px-4 py-8">
      <div className="mx-auto w-full max-w-5xl rounded-2xl bg-white shadow-xl">
        <header className="flex items-start justify-between border-b border-slate-200 px-6 py-5">
          <div>
            <h2 className="text-xl font-bold text-slate-950">
              {supplier
                ? "Edit supplier"
                : "Create supplier"}
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              {supplier
                ? "Update supplier contact, financial, and business information."
                : "Add a new supplier for your company or an accessible branch."}
            </p>
          </div>

          <button
            type="button"
            onClick={onCancel}
            disabled={isSaving}
            aria-label="Close supplier form"
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
                    htmlFor="supplier-branch"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Branch
                  </label>

                  <select
                    id="supplier-branch"
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
                    <option value="">
                      Company-wide supplier
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
                    Leave this as company-wide when
                    the supplier is not restricted to
                    one branch.
                  </p>

                  {errors.branch_id && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.branch_id}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="supplier-name"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Supplier name
                  </label>

                  <input
                    id="supplier-name"
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
                    htmlFor="supplier-code"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Supplier code
                  </label>

                  <input
                    id="supplier-code"
                    required
                    maxLength={50}
                    placeholder="SUP-001"
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

                <div className="md:col-span-2">
                  <label
                    htmlFor="supplier-business-name"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Registered business name
                  </label>

                  <input
                    id="supplier-business-name"
                    maxLength={255}
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
                    htmlFor="supplier-email"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Email
                  </label>

                  <input
                    id="supplier-email"
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
                    htmlFor="supplier-phone"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Phone
                  </label>

                  <input
                    id="supplier-phone"
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
                    htmlFor="supplier-alternate-phone"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Alternate phone
                  </label>

                  <input
                    id="supplier-alternate-phone"
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
                    htmlFor="supplier-website"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Website
                  </label>

                  <input
                    id="supplier-website"
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
                    htmlFor="supplier-tax-number"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Tax identification number
                  </label>

                  <input
                    id="supplier-tax-number"
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
                    htmlFor="supplier-trade-license"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Trade license number
                  </label>

                  <input
                    id="supplier-trade-license"
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
                Address
              </h3>

              <div className="grid gap-5 md:grid-cols-2">
                <div className="md:col-span-2">
                  <label
                    htmlFor="supplier-address-line-1"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Address line 1
                  </label>

                  <input
                    id="supplier-address-line-1"
                    maxLength={255}
                    value={form.addressLine1}
                    onChange={(event) =>
                      updateField(
                        "addressLine1",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.address_line_1 && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.address_line_1}
                    </p>
                  )}
                </div>

                <div className="md:col-span-2">
                  <label
                    htmlFor="supplier-address-line-2"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Address line 2
                  </label>

                  <input
                    id="supplier-address-line-2"
                    maxLength={255}
                    value={form.addressLine2}
                    onChange={(event) =>
                      updateField(
                        "addressLine2",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.address_line_2 && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.address_line_2}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="supplier-city"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    City
                  </label>

                  <input
                    id="supplier-city"
                    maxLength={100}
                    value={form.city}
                    onChange={(event) =>
                      updateField(
                        "city",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.city && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.city}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="supplier-district"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    District
                  </label>

                  <input
                    id="supplier-district"
                    maxLength={100}
                    value={form.district}
                    onChange={(event) =>
                      updateField(
                        "district",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.district && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.district}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="supplier-postal-code"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Postal code
                  </label>

                  <input
                    id="supplier-postal-code"
                    maxLength={20}
                    value={form.postalCode}
                    onChange={(event) =>
                      updateField(
                        "postalCode",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.postal_code && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.postal_code}
                    </p>
                  )}
                </div>

                <div>
                  <label
                    htmlFor="supplier-country"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Country
                  </label>

                  <input
                    id="supplier-country"
                    required
                    maxLength={100}
                    value={form.country}
                    onChange={(event) =>
                      updateField(
                        "country",
                        event.target.value
                      )
                    }
                    disabled={isSaving}
                    className={inputClassName}
                  />

                  {errors.country && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.country}
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
                    htmlFor="supplier-payment-term"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Payment term days
                  </label>

                  <input
                    id="supplier-payment-term"
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
                    htmlFor="supplier-credit-limit"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Credit limit
                  </label>

                  <input
                    id="supplier-credit-limit"
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
                    htmlFor="supplier-opening-balance"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Opening balance
                  </label>

                  <input
                    id="supplier-opening-balance"
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
                    htmlFor="supplier-opening-balance-type"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Balance type
                  </label>

                  <select
                    id="supplier-opening-balance-type"
                    value={
                      form.openingBalanceType
                    }
                    onChange={(event) =>
                      updateField(
                        "openingBalanceType",
                        event.target
                          .value as SupplierOpeningBalanceType
                      )
                    }
                    disabled={isSaving}
                    className={selectClassName}
                  >
                    <option value="payable">
                      Payable
                    </option>

                    <option value="receivable">
                      Receivable
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
                    htmlFor="supplier-notes"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Notes
                  </label>

                  <textarea
                    id="supplier-notes"
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

                  Active supplier
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
                form.name.trim() === "" ||
                form.code.trim() === ""
              }
              className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
            >
              {isSaving
                ? "Saving..."
                : supplier
                  ? "Save changes"
                  : "Create supplier"}
            </button>
          </footer>
        </form>
      </div>
    </div>
  );
}