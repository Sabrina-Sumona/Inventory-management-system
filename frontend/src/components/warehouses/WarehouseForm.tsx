"use client";

import {
  type FormEvent,
  useMemo,
  useState,
} from "react";

import type { Branch } from "@/types/branch";
import type {
  Warehouse,
  WarehousePayload,
} from "@/types/warehouse";

interface WarehouseFormValues {
  branchId: string;
  name: string;
  code: string;
  email: string;
  phone: string;
  address: string;
  city: string;
  district: string;
  postalCode: string;
  isPrimary: boolean;
  isActive: boolean;
}

interface WarehouseFormProps {
  warehouse: Warehouse | null;
  branches: Branch[];
  isSaving: boolean;
  errors: Record<string, string>;
  onCancel: () => void;
  onSubmit: (
    payload: WarehousePayload
  ) => Promise<void>;
}

const emptyForm: WarehouseFormValues = {
  branchId: "",
  name: "",
  code: "",
  email: "",
  phone: "",
  address: "",
  city: "",
  district: "",
  postalCode: "",
  isPrimary: false,
  isActive: true,
};

function nullableValue(
  value: string
): string | null {
  const trimmedValue = value.trim();

  return trimmedValue === ""
    ? null
    : trimmedValue;
}

function warehouseToForm(
  warehouse: Warehouse | null
): WarehouseFormValues {
  if (!warehouse) {
    return {
      ...emptyForm,
    };
  }

  return {
    branchId: String(warehouse.branch_id),
    name: warehouse.name,
    code: warehouse.code,
    email: warehouse.email ?? "",
    phone: warehouse.phone ?? "",
    address: warehouse.address ?? "",
    city: warehouse.city ?? "",
    district: warehouse.district ?? "",
    postalCode: warehouse.postal_code ?? "",
    isPrimary: warehouse.is_primary,
    isActive: warehouse.is_active,
  };
}

export function WarehouseForm({
  warehouse,
  branches,
  isSaving,
  errors,
  onCancel,
  onSubmit,
}: WarehouseFormProps) {
  const [form, setForm] =
    useState<WarehouseFormValues>(
      warehouseToForm(warehouse)
    );

  const availableBranches =
    useMemo<Branch[]>(() => {
      if (!warehouse) {
        return branches;
      }

      const existingBranchIsAvailable =
        branches.some(
          (branch) =>
            branch.id === warehouse.branch_id
        );

      if (existingBranchIsAvailable) {
        return branches;
      }

      const currentBranch: Branch = {
        id: warehouse.branch.id,
        company_id: warehouse.company_id,
        name: warehouse.branch.name,
        code: warehouse.branch.code,
        email: null,
        phone: null,
        address: null,
        city: null,
        district: null,
        postal_code: null,
        is_head_office:
          warehouse.branch.is_head_office,
        is_active: true,
        company: warehouse.company,
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
    }, [branches, warehouse]);

  function updateField<
    Key extends keyof WarehouseFormValues
  >(
    field: Key,
    value: WarehouseFormValues[Key]
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

    const branchId = Number(form.branchId);

    if (
      !Number.isInteger(branchId) ||
      branchId <= 0
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
      email: nullableValue(form.email),
      phone: nullableValue(form.phone),
      address: nullableValue(form.address),
      city: nullableValue(form.city),
      district: nullableValue(
        form.district
      ),
      postal_code: nullableValue(
        form.postalCode
      ),
      is_primary: form.isPrimary,
      is_active: form.isActive,
    });
  }

  return (
    <div className="fixed inset-0 z-[70] overflow-y-auto bg-slate-950/60 px-4 py-8">
      <div className="mx-auto w-full max-w-3xl rounded-2xl bg-white shadow-xl">
        <header className="flex items-start justify-between border-b border-slate-200 px-6 py-5">
          <div>
            <h2 className="text-xl font-bold text-slate-950">
              {warehouse
                ? "Edit warehouse"
                : "Create warehouse"}
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              {warehouse
                ? "Update warehouse information and settings."
                : "Add a warehouse under an accessible branch."}
            </p>
          </div>

          <button
            type="button"
            onClick={onCancel}
            disabled={isSaving}
            aria-label="Close warehouse form"
            className="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
          >
            ✕
          </button>
        </header>

        <form
          onSubmit={handleSubmit}
          className="p-6"
        >
          <div className="grid gap-5 md:grid-cols-2">
            <div className="md:col-span-2">
              <label
                htmlFor="warehouse-branch"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Branch
              </label>

              <select
                id="warehouse-branch"
                required
                value={form.branchId}
                onChange={(event) =>
                  updateField(
                    "branchId",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
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

              {errors.branch_id && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.branch_id}
                </p>
              )}

              {availableBranches.length ===
                0 && (
                <p className="mt-2 text-sm text-amber-700">
                  No accessible active branches
                  were found. Assign the user to
                  an active branch before creating
                  a warehouse.
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="warehouse-name"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Warehouse name
              </label>

              <input
                id="warehouse-name"
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
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
              />

              {errors.name && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.name}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="warehouse-code"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Warehouse code
              </label>

              <input
                id="warehouse-code"
                required
                maxLength={50}
                placeholder="DHAKA-WAREHOUSE"
                value={form.code}
                onChange={(event) =>
                  updateField(
                    "code",
                    event.target.value.toUpperCase()
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 uppercase outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
              />

              {errors.code && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.code}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="warehouse-email"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Email
              </label>

              <input
                id="warehouse-email"
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
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
              />

              {errors.email && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.email}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="warehouse-phone"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Phone
              </label>

              <input
                id="warehouse-phone"
                maxLength={30}
                value={form.phone}
                onChange={(event) =>
                  updateField(
                    "phone",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
              />

              {errors.phone && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.phone}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="warehouse-city"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                City
              </label>

              <input
                id="warehouse-city"
                maxLength={100}
                value={form.city}
                onChange={(event) =>
                  updateField(
                    "city",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
              />

              {errors.city && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.city}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="warehouse-district"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                District
              </label>

              <input
                id="warehouse-district"
                maxLength={100}
                value={form.district}
                onChange={(event) =>
                  updateField(
                    "district",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
              />

              {errors.district && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.district}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="warehouse-postal-code"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Postal code
              </label>

              <input
                id="warehouse-postal-code"
                maxLength={20}
                value={form.postalCode}
                onChange={(event) =>
                  updateField(
                    "postalCode",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className="h-11 w-full rounded-lg border border-slate-300 px-3.5 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
              />

              {errors.postal_code && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.postal_code}
                </p>
              )}
            </div>

            <div className="flex items-center gap-6 pt-7">
              <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input
                  type="checkbox"
                  checked={form.isPrimary}
                  onChange={(event) => {
                    const isPrimary =
                      event.target.checked;

                    setForm((current) => ({
                      ...current,
                      isPrimary,
                      isActive: isPrimary
                        ? true
                        : current.isActive,
                    }));
                  }}
                  disabled={isSaving}
                  className="h-4 w-4 rounded border-slate-300"
                />

                Primary warehouse
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
                  disabled={
                    isSaving ||
                    form.isPrimary
                  }
                  className="h-4 w-4 rounded border-slate-300"
                />

                Active
              </label>
            </div>

            {errors.is_primary && (
              <p className="text-sm text-red-600 md:col-span-2">
                {errors.is_primary}
              </p>
            )}

            {errors.is_active && (
              <p className="text-sm text-red-600 md:col-span-2">
                {errors.is_active}
              </p>
            )}

            <div className="md:col-span-2">
              <label
                htmlFor="warehouse-address"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Address
              </label>

              <textarea
                id="warehouse-address"
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
                className="w-full rounded-lg border border-slate-300 px-3.5 py-3 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100"
              />

              {errors.address && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.address}
                </p>
              )}
            </div>
          </div>

          <footer className="mt-7 flex justify-end gap-3 border-t border-slate-200 pt-5">
            <button
              type="button"
              onClick={onCancel}
              disabled={isSaving}
              className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Cancel
            </button>

            <button
              type="submit"
              disabled={
                isSaving ||
                availableBranches.length ===
                  0 ||
                form.branchId === ""
              }
              className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
            >
              {isSaving
                ? "Saving..."
                : warehouse
                  ? "Save changes"
                  : "Create warehouse"}
            </button>
          </footer>
        </form>
      </div>
    </div>
  );
}