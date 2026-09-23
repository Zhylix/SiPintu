<div x-data="toastNotificationComponent()"
    x-on:show-toast.window="addToast($event.detail.message, $event.detail.type ?? ($event.detail.isSuccess !== undefined ? ($event.detail.isSuccess ? 'success' : 'error') : 'success'), $event.detail.title, $event.detail.duration)"
    x-on:favorite-updated.window="if ($event.detail.message) addToast($event.detail.message, 'favorite', $event.detail.is_favorited ? 'Ditambahkan ke Favorit' : 'Dihapus dari Favorit')"
    class="fixed top-4 right-4 sm:top-6 sm:right-6 z-[9999] flex flex-col space-y-2.5 max-w-[380px] w-[calc(100%-2rem)] sm:w-[380px] pointer-events-none"
    role="region" 
    aria-live="polite"
    aria-label="Pemberitahuan Sistem">
    
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
             x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-5 scale-[0.98]"
             x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-[0.96] translate-y-1 sm:translate-x-2"
             @mouseenter="pauseToast(toast)"
             @mouseleave="resumeToast(toast)"
             class="pointer-events-auto relative flex items-start gap-3 p-3.5 sm:p-4 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200/80 shadow-[0_12px_32px_-4px_rgba(15,23,42,0.12),0_4px_12px_-2px_rgba(15,23,42,0.04)] transition-all duration-200 hover:shadow-[0_16px_36px_-4px_rgba(15,23,42,0.15)] w-full">
            
            <!-- Status Icon Refined Squircle -->
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5 border"
                 :class="{
                     'bg-emerald-50/80 border-emerald-200/70 text-emerald-700': toast.type === 'success',
                     'bg-rose-50/80 border-rose-200/70 text-rose-600': toast.type === 'error',
                     'bg-amber-50/80 border-amber-200/70 text-amber-600': toast.type === 'warning',
                     'bg-sky-50/80 border-sky-200/70 text-sky-600': toast.type === 'info',
                     'bg-amber-50/80 border-amber-200/70 text-amber-500': toast.type === 'favorite'
                 }">
                
                <!-- SUCCESS: Minimalist Geometric Checkmark -->
                <template x-if="toast.type === 'success'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </template>

                <!-- ERROR: Refined Crisp Cross -->
                <template x-if="toast.type === 'error'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>

                <!-- WARNING: Clean Alert Triangle -->
                <template x-if="toast.type === 'warning'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </template>

                <!-- INFO: Clean Minimal Info Circle -->
                <template x-if="toast.type === 'info'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </template>

                <!-- FAVORITE: Gold Star -->
                <template x-if="toast.type === 'favorite'">
                    <svg class="w-4 h-4 fill-amber-400" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </template>
            </div>

            <!-- Text Content with Sophisticated Typography -->
            <div class="min-w-0 flex-1 pt-0.5">
                <div class="text-[13px] font-bold text-slate-900 tracking-tight leading-snug" x-text="toast.title"></div>
                <div class="text-[12px] text-slate-500 font-medium leading-relaxed break-words mt-0.5" x-text="toast.message"></div>
            </div>

            <!-- Minimal Ghost Close Button -->
            <button @click="removeToast(toast.id)" 
                    type="button" 
                    aria-label="Tutup notifikasi"
                    class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors shrink-0 -mr-1 -mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>

<script>
    (function () {
        // Safe global queue in case methods are called prior to Alpine initialization
        window._toastQueue = window._toastQueue || [];
        if (!window.toast) {
            window.toast = {
                success: function(msg, title, duration) { window._toastQueue.push([msg, 'success', title, duration]); },
                error: function(msg, title, duration) { window._toastQueue.push([msg, 'error', title, duration]); },
                warning: function(msg, title, duration) { window._toastQueue.push([msg, 'warning', title, duration]); },
                info: function(msg, title, duration) { window._toastQueue.push([msg, 'info', title, duration]); },
                favorite: function(msg, title, duration) { window._toastQueue.push([msg, 'favorite', title, duration]); },
                show: function(msg, type, title, duration) { window._toastQueue.push([msg, type, title, duration]); }
            };
        }
        if (!window.showToast) {
            window.showToast = function(msg, type, title, duration) {
                window._toastQueue.push([msg, type || 'success', title, duration]);
            };
        }

        function toastNotificationComponent() {
            return {
                toasts: [],
                addToast(message, type = 'success', title = null, duration = 4000) {
                    if (!message) return;

                    // Normalisasi tipe notifikasi
                    let toastType = 'success';
                    if (typeof type === 'boolean') {
                        toastType = type ? 'success' : 'error';
                    } else if (typeof type === 'string') {
                        const lower = type.toLowerCase();
                        if (['danger', 'failed'].includes(lower)) toastType = 'error';
                        else if (['warn'].includes(lower)) toastType = 'warning';
                        else if (['success', 'error', 'warning', 'info', 'favorite'].includes(lower)) toastType = lower;
                        else toastType = 'info';
                    }

                    // Standar judul yang elegan & ringkas
                    let toastTitle = title;
                    if (!toastTitle) {
                        switch(toastType) {
                            case 'success': toastTitle = 'Berhasil'; break;
                            case 'error': toastTitle = 'Perhatian'; break;
                            case 'warning': toastTitle = 'Peringatan'; break;
                            case 'info': toastTitle = 'Informasi'; break;
                            case 'favorite': toastTitle = 'Favorit'; break;
                            default: toastTitle = 'SiPintu'; break;
                        }
                    }

                    const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
                    const newToast = {
                        id,
                        title: toastTitle,
                        message: message,
                        type: toastType,
                        duration: duration,
                        remaining: duration,
                        startTime: Date.now(),
                        timer: null
                    };

                    this.toasts.unshift(newToast);
                    this.startTimer(newToast);
                },

                startTimer(toast) {
                    toast.startTime = Date.now();
                    toast.timer = setTimeout(() => {
                        this.removeToast(toast.id);
                    }, toast.remaining);
                },

                pauseToast(toast) {
                    clearTimeout(toast.timer);
                    const elapsed = Date.now() - toast.startTime;
                    toast.remaining = Math.max(500, toast.remaining - elapsed);
                },

                resumeToast(toast) {
                    this.startTimer(toast);
                },

                removeToast(id) {
                    const index = this.toasts.findIndex(t => t.id === id);
                    if (index !== -1) {
                        clearTimeout(this.toasts[index].timer);
                        this.toasts.splice(index, 1);
                    }
                },

                init() {
                    window.toast = {
                        success: (msg, title, duration) => this.addToast(msg, 'success', title, duration),
                        error: (msg, title, duration) => this.addToast(msg, 'error', title, duration),
                        warning: (msg, title, duration) => this.addToast(msg, 'warning', title, duration),
                        info: (msg, title, duration) => this.addToast(msg, 'info', title, duration),
                        favorite: (msg, title, duration) => this.addToast(msg, 'favorite', title, duration),
                        show: (msg, type, title, duration) => this.addToast(msg, type, title, duration)
                    };

                    window.showToast = (msg, type = 'success', title = null, duration = 4000) => {
                        this.addToast(msg, type, title, duration);
                    };

                    // Jalankan antrian jika ada pemanggilan sebelum init
                    if (window._toastQueue && window._toastQueue.length > 0) {
                        while (window._toastQueue.length > 0) {
                            const queued = window._toastQueue.shift();
                            this.addToast(queued[0], queued[1], queued[2], queued[3]);
                        }
                    }

                    // Otomatis tangkap Flash Session Laravel secara aman tanpa bentrok tanda kutip HTML
                    @if(session('success'))
                        this.addToast({!! json_encode(session('success')) !!}, 'success', 'Berhasil');
                    @endif

                    @if(session('error'))
                        this.addToast({!! json_encode(session('error')) !!}, 'error', 'Pemberitahuan');
                    @endif

                    @if(session('warning'))
                        this.addToast({!! json_encode(session('warning')) !!}, 'warning', 'Peringatan');
                    @endif

                    @if(session('info'))
                        this.addToast({!! json_encode(session('info')) !!}, 'info', 'Informasi');
                    @endif

                    @if(session('status'))
                        this.addToast({!! json_encode(session('status')) !!}, 'info', 'Status');
                    @endif
                }
            };
        }

        window.toastNotificationComponent = toastNotificationComponent;

        if (window.Alpine) {
            window.Alpine.data('toastNotificationComponent', toastNotificationComponent);
        } else {
            document.addEventListener('alpine:init', function () {
                window.Alpine.data('toastNotificationComponent', toastNotificationComponent);
            });
        }
    })();
</script>
