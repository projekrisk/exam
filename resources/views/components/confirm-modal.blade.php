<div id="app-confirm-modal" class="hidden fixed inset-0 w-screen h-screen z-[110] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeConfirmModal()">
  <div id="app-confirm-box" onclick="event.stopPropagation()" class="bg-white border border-notion-border rounded-xl w-full max-w-md p-6 shadow-xl space-y-4 transform scale-95 transition-all duration-200">
    <div class="space-y-3">
      <div class="flex items-center gap-3">
        <div id="confirm-icon-wrap" class="w-9 h-9 rounded-lg bg-notion-redBg text-notion-red flex items-center justify-center shrink-0">
          <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <h3 id="confirm-title" class="font-bold text-base text-notion-text">Konfirmasi Tindakan</h3>
      </div>
      <p id="confirm-message" class="text-sm text-notion-gray leading-relaxed">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
    </div>

    <div class="flex justify-end gap-2 pt-2 border-t border-notion-border">
      <button type="button" onclick="closeConfirmModal()" class="px-4 py-2 bg-white border border-notion-border text-notion-text text-sm font-medium rounded-md hover:bg-notion-hover transition-colors">
        Batal
      </button>
      <button type="button" id="confirm-submit-btn" class="px-4 py-2 bg-notion-red text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors">
        Ya, Hapus
      </button>
    </div>
  </div>
</div>
