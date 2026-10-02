"use client";

import { useEffect, useState } from "react";

/**
 * Client widgets for the CMS page editor: a live-preview modal (EN/BN toggle,
 * mirroring `dashboard/cms/edit.blade.php`) and a media-library picker that
 * opens `/dashboard/media?select=1` and listens for the `media-selected`
 * message the media screen posts.
 */

export function MediaPickerButton({ targetName, className }: { targetName: string; className?: string }) {
  return (
    <button
      type="button"
      className={className ?? "mt-1 inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"}
      onClick={() => window.dispatchEvent(new CustomEvent("cms:open-media", { detail: targetName }))}
    >
      Media
    </button>
  );
}

export function CmsPreview({ page }: { page: string }) {
  const [previewOpen, setPreviewOpen] = useState(false);
  const [lang, setLang] = useState<"en" | "bn">("en");
  const [mediaOpen, setMediaOpen] = useState(false);
  const [mediaTarget, setMediaTarget] = useState<string | null>(null);

  const previewSrc = `/${page === "home" ? "" : page}?lang=${lang}`;

  useEffect(() => {
    function openMedia(event: Event) {
      const detail = (event as CustomEvent).detail;
      if (typeof detail === "string") {
        setMediaTarget(detail);
        setMediaOpen(true);
      }
    }

    function onMessage(event: MessageEvent) {
      const data = event.data as { type?: string; url?: string } | null;
      if (!data?.type) return;

      if (data.type === "media-close") {
        setMediaOpen(false);
        return;
      }

      if (data.type === "media-selected" && data.url && mediaTarget) {
        const input = document.querySelector<HTMLInputElement>(`input[name="${mediaTarget}"]`);
        if (input) input.value = data.url;
        setMediaOpen(false);
      }
    }

    function onKey(event: KeyboardEvent) {
      if (event.key === "Escape") {
        setPreviewOpen(false);
        setMediaOpen(false);
      }
    }

    window.addEventListener("cms:open-media", openMedia);
    window.addEventListener("message", onMessage);
    window.addEventListener("keydown", onKey);
    return () => {
      window.removeEventListener("cms:open-media", openMedia);
      window.removeEventListener("message", onMessage);
      window.removeEventListener("keydown", onKey);
    };
  }, [mediaTarget]);

  return (
    <>
      <button
        type="button"
        onClick={() => setPreviewOpen(true)}
        className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
      >
        Preview
      </button>

      {previewOpen ? (
        <div className="fixed inset-0 z-50 flex flex-col bg-black/70 p-4" role="dialog" aria-modal="true" aria-label="Page preview">
          <div className="mx-auto flex h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
              <h3 className="text-sm font-semibold text-slate-900">Live preview — {page.replace(/-/g, " ")}</h3>
              <div className="flex items-center gap-2">
                <div className="flex overflow-hidden rounded-lg border border-slate-300">
                  <button type="button" onClick={() => setLang("en")} className={`px-3 py-1 text-xs font-semibold ${lang === "en" ? "bg-brand-600 text-white" : "text-slate-600 hover:bg-slate-50"}`}>EN</button>
                  <button type="button" onClick={() => setLang("bn")} className={`px-3 py-1 text-xs font-semibold ${lang === "bn" ? "bg-brand-600 text-white" : "text-slate-600 hover:bg-slate-50"}`}>বাংলা</button>
                </div>
                <button type="button" onClick={() => setPreviewOpen(false)} aria-label="Close preview" className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                  ✕
                </button>
              </div>
            </div>
            <iframe src={previewSrc} title="Page preview" className="flex-1 border-0 bg-white" />
          </div>
        </div>
      ) : null}

      {mediaOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
          <div className="flex h-[80vh] w-[80vw] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-200 px-5 py-3">
              <h3 className="text-sm font-semibold text-slate-900">Media library</h3>
              <button type="button" onClick={() => setMediaOpen(false)} aria-label="Close media browser" className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                ✕
              </button>
            </div>
            <iframe src="/dashboard/media?select=1" title="Media library" className="flex-1 border-0" />
          </div>
        </div>
      ) : null}
    </>
  );
}
