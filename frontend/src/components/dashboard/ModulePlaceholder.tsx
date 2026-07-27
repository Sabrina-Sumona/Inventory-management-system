interface ModulePlaceholderProps {
  title: string;
  description: string;
}

export function ModulePlaceholder({
  title,
  description,
}: ModulePlaceholderProps) {
  return (
    <section className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-lg font-bold text-emerald-800">
        DS
      </div>

      <h2 className="mt-6 text-2xl font-bold text-slate-950">
        {title}
      </h2>

      <p className="mt-3 max-w-2xl leading-7 text-slate-600">
        {description}
      </p>

      <div className="mt-6 inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-800">
        Module implementation pending
      </div>
    </section>
  );
}