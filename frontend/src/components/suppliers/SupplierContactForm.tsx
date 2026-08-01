"use client";

import {
  type ChangeEvent,
  type FormEvent,
  useState,
} from "react";

import type {
  Supplier,
  SupplierContact,
  SupplierContactPayload,
  SupplierContactType,
} from "@/types/supplier";

interface SupplierContactFormProps {
  supplier: Supplier;
  contact: SupplierContact | null;
  isSaving: boolean;
  errors: Record<string, string>;
  onCancel: () => void;
  onSubmit: (
    payload: SupplierContactPayload
  ) => Promise<void>;
}

interface SupplierContactFormState {
  name: string;
  designation: string;
  department: string;
  contact_type: SupplierContactType;
  email: string;
  phone: string;
  alternate_phone: string;
  is_primary: boolean;
  is_active: boolean;
  notes: string;
}

function createInitialState(
  contact: SupplierContact | null
): SupplierContactFormState {
  return {
    name: contact?.name ?? "",
    designation: contact?.designation ?? "",
    department: contact?.department ?? "",
    contact_type:
      contact?.contact_type ?? "general",
    email: contact?.email ?? "",
    phone: contact?.phone ?? "",
    alternate_phone:
      contact?.alternate_phone ?? "",
    is_primary:
      contact?.is_primary ?? false,
    is_active:
      contact?.is_active ?? true,
    notes: contact?.notes ?? "",
  };
}

function optionalString(
  value: string
): string | null {
  const normalizedValue = value.trim();

  return normalizedValue.length > 0
    ? normalizedValue
    : null;
}

export function SupplierContactForm({
  supplier,
  contact,
  isSaving,
  errors,
  onCancel,
  onSubmit,
}: SupplierContactFormProps) {
  const [form, setForm] =
    useState<SupplierContactFormState>(
      createInitialState(contact)
    );

  function handleTextChange(
    event: ChangeEvent<
      HTMLInputElement |
        HTMLTextAreaElement
    >
  ): void {
    const { name, value } = event.target;

    setForm((currentForm) => ({
      ...currentForm,
      [name]: value,
    }));
  }

  function handleSelectChange(
    event: ChangeEvent<HTMLSelectElement>
  ): void {
    const { name, value } = event.target;

    setForm((currentForm) => ({
      ...currentForm,
      [name]: value,
    }));
  }

  function handleCheckboxChange(
    event: ChangeEvent<HTMLInputElement>
  ): void {
    const { name, checked } = event.target;

    setForm((currentForm) => ({
      ...currentForm,
      [name]: checked,
    }));
  }

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    const payload: SupplierContactPayload = {
      supplier_id: supplier.id,
      name: form.name.trim(),
      designation: optionalString(
        form.designation
      ),
      department: optionalString(
        form.department
      ),
      contact_type: form.contact_type,
      email: optionalString(form.email),
      phone: optionalString(form.phone),
      alternate_phone: optionalString(
        form.alternate_phone
      ),
      is_primary: form.is_primary,
      is_active: form.is_active,
      notes: optionalString(form.notes),
    };

    await onSubmit(payload);
  }

  const fieldClassName =
    "h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100";

  const textareaClassName =
    "min-h-28 w-full resize-y rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100";

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="supplier-contact-form-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
    >
      <div className="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
          <div>
            <h2
              id="supplier-contact-form-title"
              className="text-xl font-bold text-slate-950"
            >
              {contact
                ? "Edit Supplier Contact"
                : "Add Supplier Contact"}
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              Supplier:{" "}
              <span className="font-semibold text-slate-700">
                {supplier.name}
              </span>
            </p>
          </div>

          <button
            type="button"
            onClick={onCancel}
            disabled={isSaving}
            aria-label="Close supplier contact form"
            className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Close
          </button>
        </div>

        <form
          onSubmit={handleSubmit}
          className="space-y-6 p-6"
        >
          <section>
            <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">
              Contact Information
            </h3>

            <div className="mt-4 grid gap-5 md:grid-cols-2">
              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Contact name{" "}
                  <span className="text-red-600">
                    *
                  </span>
                </span>

                <input
                  type="text"
                  name="name"
                  value={form.name}
                  onChange={handleTextChange}
                  disabled={isSaving}
                  placeholder="Enter contact name"
                  className={fieldClassName}
                />

                {errors.name && (
                  <span className="mt-1 block text-xs text-red-600">
                    {errors.name}
                  </span>
                )}
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Contact type{" "}
                  <span className="text-red-600">
                    *
                  </span>
                </span>

                <select
                  name="contact_type"
                  value={form.contact_type}
                  onChange={handleSelectChange}
                  disabled={isSaving}
                  className={fieldClassName}
                >
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

                {errors.contact_type && (
                  <span className="mt-1 block text-xs text-red-600">
                    {errors.contact_type}
                  </span>
                )}
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Designation
                </span>

                <input
                  type="text"
                  name="designation"
                  value={form.designation}
                  onChange={handleTextChange}
                  disabled={isSaving}
                  placeholder="Sales Manager"
                  className={fieldClassName}
                />

                {errors.designation && (
                  <span className="mt-1 block text-xs text-red-600">
                    {errors.designation}
                  </span>
                )}
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Department
                </span>

                <input
                  type="text"
                  name="department"
                  value={form.department}
                  onChange={handleTextChange}
                  disabled={isSaving}
                  placeholder="Sales Department"
                  className={fieldClassName}
                />

                {errors.department && (
                  <span className="mt-1 block text-xs text-red-600">
                    {errors.department}
                  </span>
                )}
              </label>
            </div>
          </section>

          <section className="border-t border-slate-200 pt-6">
            <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">
              Communication Details
            </h3>

            <div className="mt-4 grid gap-5 md:grid-cols-2">
              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Email
                </span>

                <input
                  type="email"
                  name="email"
                  value={form.email}
                  onChange={handleTextChange}
                  disabled={isSaving}
                  placeholder="contact@example.com"
                  className={fieldClassName}
                />

                {errors.email && (
                  <span className="mt-1 block text-xs text-red-600">
                    {errors.email}
                  </span>
                )}
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Phone
                </span>

                <input
                  type="text"
                  name="phone"
                  value={form.phone}
                  onChange={handleTextChange}
                  disabled={isSaving}
                  placeholder="01700000000"
                  className={fieldClassName}
                />

                {errors.phone && (
                  <span className="mt-1 block text-xs text-red-600">
                    {errors.phone}
                  </span>
                )}
              </label>

              <label className="block md:col-span-2">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Alternate phone
                </span>

                <input
                  type="text"
                  name="alternate_phone"
                  value={form.alternate_phone}
                  onChange={handleTextChange}
                  disabled={isSaving}
                  placeholder="01800000000"
                  className={fieldClassName}
                />

                {errors.alternate_phone && (
                  <span className="mt-1 block text-xs text-red-600">
                    {errors.alternate_phone}
                  </span>
                )}
              </label>
            </div>
          </section>

          <section className="border-t border-slate-200 pt-6">
            <h3 className="text-sm font-bold uppercase tracking-wide text-slate-700">
              Status and Notes
            </h3>

            <div className="mt-4 grid gap-4 md:grid-cols-2">
              <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
                <input
                  type="checkbox"
                  name="is_primary"
                  checked={form.is_primary}
                  onChange={handleCheckboxChange}
                  disabled={isSaving}
                  className="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
                />

                <span>
                  <span className="block text-sm font-semibold text-slate-800">
                    Primary contact
                  </span>

                  <span className="mt-1 block text-xs text-slate-500">
                    Making this contact primary will
                    remove the primary status from the
                    supplier&apos;s previous primary
                    contact.
                  </span>
                </span>
              </label>

              <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
                <input
                  type="checkbox"
                  name="is_active"
                  checked={form.is_active}
                  onChange={handleCheckboxChange}
                  disabled={isSaving}
                  className="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
                />

                <span>
                  <span className="block text-sm font-semibold text-slate-800">
                    Active contact
                  </span>

                  <span className="mt-1 block text-xs text-slate-500">
                    Inactive contacts remain stored but
                    are marked unavailable for normal
                    operational use.
                  </span>
                </span>
              </label>
            </div>

            {errors.is_primary && (
              <p className="mt-2 text-xs text-red-600">
                {errors.is_primary}
              </p>
            )}

            {errors.is_active && (
              <p className="mt-2 text-xs text-red-600">
                {errors.is_active}
              </p>
            )}

            <label className="mt-5 block">
              <span className="mb-2 block text-sm font-semibold text-slate-700">
                Notes
              </span>

              <textarea
                name="notes"
                value={form.notes}
                onChange={handleTextChange}
                disabled={isSaving}
                placeholder="Add any relevant notes about this contact"
                className={textareaClassName}
              />

              {errors.notes && (
                <span className="mt-1 block text-xs text-red-600">
                  {errors.notes}
                </span>
              )}
            </label>
          </section>

          {errors.supplier_id && (
            <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {errors.supplier_id}
            </div>
          )}

          <div className="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
            <button
              type="button"
              onClick={onCancel}
              disabled={isSaving}
              className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Cancel
            </button>

            <button
              type="submit"
              disabled={
                isSaving ||
                form.name.trim().length === 0
              }
              className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-300"
            >
              {isSaving
                ? "Saving..."
                : contact
                  ? "Update contact"
                  : "Create contact"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}