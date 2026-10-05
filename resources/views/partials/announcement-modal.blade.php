@php
    $siteLogoUrl = \App\Models\Setting::getLogoUrl();
    
    // Check if there is an active announcement for the current user
    $userRole = auth()->check() ? auth()->user()->role : null;
    $initialActiveAnnouncement = \App\Models\Announcement::active()
        ->forWeb()
        ->forRole($userRole)
        ->latest()
        ->first();

    $initialData = null;
    if ($initialActiveAnnouncement) {
        $initialData = [
            'id' => $initialActiveAnnouncement->id,
            'title' => $initialActiveAnnouncement->title,
            'content' => $initialActiveAnnouncement->content,
            'type' => $initialActiveAnnouncement->type ?? 'info',
            'target_role' => $initialActiveAnnouncement->target_role ?? 'all',
            'published_at_diff' => $initialActiveAnnouncement->published_at ? $initialActiveAnnouncement->published_at->diffForHumans() : 'Baru saja',
            'published_at_formatted' => $initialActiveAnnouncement->published_at ? $initialActiveAnnouncement->published_at->format('d M Y, H:i') : null,
            'updated_at' => $initialActiveAnnouncement->updated_at ? $initialActiveAnnouncement->updated_at->toISOString() : '',
            'author_name' => $initialActiveAnnouncement->author?->name ?? 'Admin Sekolah',
        ];
    }
@endphp

<div x-data="sipintuAnnouncementModal()"
     x-on:open-announcement-popup.window="openModal($event.detail)"
     x-on:keydown.escape.window="if (isOpen) closeModal()"
     class="relative z-[9990]"
     x-cloak>

    <!-- Overlay Backdrop with Frosted Glass -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeModal()"
         class="fixed inset-0 bg-slate-950/65 backdrop-blur-md transition-opacity"
         aria-hidden="true"></div>

    <!-- Modal Dialog Center Container -->
    <div x-show="isOpen"
         class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 flex items-center justify-center min-h-full">
        
        <div x-show="isOpen"
             x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.outside="closeModal()"
             class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border overflow-hidden flex flex-col my-auto transition-all"
             :class="theme.borderAccent"
             role="dialog"
             aria-modal="true"
             :aria-label="announcement ? announcement.title : 'Pengumuman Penting'">

            <!-- Decorative Ambient Glow Background -->
            <div class="absolute -top-24 -right-24 w-56 h-56 rounded-full blur-3xl pointer-events-none opacity-40"
                 :class="theme.glowOrbColor"></div>
            <div class="absolute -bottom-20 -left-20 w-48 h-48 rounded-full blur-3xl pointer-events-none opacity-30"
                 :class="theme.glowOrbColor"></div>

            <!-- Header Band with Dynamic Type Gradient -->
            <div class="relative px-6 pt-6 pb-4 border-b border-slate-100"
                 :class="theme.headerBg">
                
                <div class="flex items-start justify-between gap-4">
                    <!-- Left: Icon & Badge Combo -->
                    <div class="flex items-center space-x-3.5">
                        <!-- Distinctive Squircle Icon Container with Vibrant Gradient -->
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 text-white shadow-lg transition-transform hover:scale-105"
                             :class="[theme.iconGradient, theme.iconShadow]">
                            
                            <!-- INFO ICON -->
                            <template x-if="announcement && announcement.type === 'info'">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>

                            <!-- WARNING ICON -->
                            <template x-if="announcement && announcement.type === 'warning'">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </template>

                            <!-- DANGER / URGENT ICON -->
                            <template x-if="announcement && announcement.type === 'danger'">
                                <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </template>

                            <!-- SUCCESS ICON -->
                            <template x-if="announcement && announcement.type === 'success'">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                        </div>

                        <!-- Brand & Badge Metadata -->
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wide uppercase border flex items-center gap-1.5 shadow-2xs"
                                      :class="theme.badgeStyle">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="[theme.badgeDotColor, announcement && announcement.type === 'danger' ? 'animate-ping' : '']"></span>
                                    <span x-text="theme.categoryLabel"></span>
                                </span>

                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                    <img src="{{ $siteLogoUrl }}" class="w-3.5 h-3.5 object-contain inline-block" alt="Logo">
                                    <span>SMKN 1 BANGSRI</span>
                                </span>
                            </div>

                            <div class="flex items-center space-x-2 text-[11px] text-slate-500 font-medium">
                                <span class="inline-flex items-center gap-1 text-slate-500">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span x-text="announcement ? announcement.published_at_diff : 'Baru saja'"></span>
                                </span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200"
                                      x-text="targetRoleLabel(announcement ? announcement.target_role : 'all')"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Close Button (Ghost Style with Hover Feedback) -->
                    <button type="button"
                            @click="closeModal()"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-300"
                            aria-label="Tutup pop up pengumuman">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Announcement Title Heading -->
                <div class="mt-4">
                    <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight leading-snug break-words"
                        x-text="announcement ? announcement.title : ''"></h3>
                </div>
            </div>

            <!-- Content Body Area (Rich UX Reading Card with Scroll Support) -->
            <div class="p-6 overflow-y-auto max-h-[46vh] space-y-4">
                
                <!-- Notice Content Card -->
                <div class="rounded-2xl p-4 sm:p-5 border transition-all"
                     :class="theme.contentCardBg">
                    <p class="text-xs sm:text-sm text-slate-700 font-medium leading-relaxed whitespace-pre-line break-words"
                       x-text="announcement ? announcement.content : ''"></p>
                </div>

                <!-- Footer Metadata Tagline -->
                <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 font-medium">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Diverifikasi oleh Sistem SiPintu</span>
                    </span>
                    <span class="font-mono text-[10px]" x-text="announcement && announcement.published_at_formatted ? announcement.published_at_formatted : ''"></span>
                </div>
            </div>

            <!-- Footer Action Controls (UI/UX Prioritized) -->
            <div class="px-6 py-4 bg-slate-50/90 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                
                <!-- Option: Don't Show Again Checkbox -->
                <label class="flex items-center space-x-2 text-xs text-slate-600 cursor-pointer select-none group w-full sm:w-auto">
                    <input type="checkbox"
                           x-model="dontShowAgain"
                           class="w-4 h-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500 transition-colors">
                    <span class="font-medium group-hover:text-slate-900 transition-colors text-[11px] sm:text-xs">
                        Jangan tampilkan popup ini lagi
                    </span>
                </label>

                <!-- Actions: Copy Text & Understood Button -->
                <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
                    <!-- Copy Announcement Text Button -->
                    <button type="button"
                            @click="copyContent()"
                            class="px-3 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-1.5 shadow-2xs hover:border-slate-300 active:scale-95"
                            title="Salin isi pengumuman ke clipboard">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span class="hidden xs:inline sm:inline">Salin</span>
                    </button>

                    <!-- Primary Confirm CTA -->
                    <button type="button"
                            @click="confirmAndClose()"
                            class="flex-1 sm:flex-initial px-5 py-2 text-white text-xs font-extrabold rounded-xl transition-all shadow-md active:scale-95 flex items-center justify-center space-x-1.5"
                            :class="[theme.buttonGradient, theme.buttonShadow]">
                        <span>Saya Mengerti</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    (function () {
        window.__sipintuActiveAnnouncement = @js($initialData);

        function sipintuAnnouncementModal(config = {}) {
            return {
                isOpen: false,
                announcement: (config && config.initialAnnouncement) ? config.initialAnnouncement : (window.__sipintuActiveAnnouncement || null),
                dontShowAgain: false,

                get theme() {
                    const type = (this.announcement?.type || 'info').toLowerCase();
                    switch (type) {
                        case 'warning':
                            return {
                                categoryLabel: 'Pemberitahuan Penting',
                                headerBg: 'bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-white',
                                borderAccent: 'border-amber-200/90 shadow-amber-500/10',
                                badgeStyle: 'bg-amber-100/90 text-amber-950 border-amber-300',
                                badgeDotColor: 'bg-amber-600',
                                iconGradient: 'bg-gradient-to-tr from-amber-500 to-orange-500',
                                iconShadow: 'shadow-[0_8px_20px_-4px_rgba(245,158,11,0.4)]',
                                contentCardBg: 'bg-amber-50/50 border-amber-200/70',
                                buttonGradient: 'bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700',
                                buttonShadow: 'shadow-amber-600/25',
                                glowOrbColor: 'bg-amber-400'
                            };
                        case 'danger':
                            return {
                                categoryLabel: 'Pengumuman Mendesak',
                                headerBg: 'bg-gradient-to-br from-rose-500/10 via-red-500/5 to-white',
                                borderAccent: 'border-rose-200/90 shadow-rose-500/15',
                                badgeStyle: 'bg-rose-100/90 text-rose-950 border-rose-300',
                                badgeDotColor: 'bg-rose-600',
                                iconGradient: 'bg-gradient-to-tr from-rose-600 to-red-600',
                                iconShadow: 'shadow-[0_8px_20px_-4px_rgba(225,29,72,0.4)]',
                                contentCardBg: 'bg-rose-50/50 border-rose-200/70',
                                buttonGradient: 'bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700',
                                buttonShadow: 'shadow-rose-600/25',
                                glowOrbColor: 'bg-rose-400'
                            };
                        case 'success':
                            return {
                                categoryLabel: 'Informasi Positif',
                                headerBg: 'bg-gradient-to-br from-emerald-500/10 via-teal-500/5 to-white',
                                borderAccent: 'border-emerald-200/90 shadow-emerald-500/10',
                                badgeStyle: 'bg-emerald-100/90 text-emerald-950 border-emerald-300',
                                badgeDotColor: 'bg-emerald-600',
                                iconGradient: 'bg-gradient-to-tr from-emerald-600 to-teal-600',
                                iconShadow: 'shadow-[0_8px_20px_-4px_rgba(5,150,105,0.4)]',
                                contentCardBg: 'bg-emerald-50/50 border-emerald-200/70',
                                buttonGradient: 'bg-gradient-to-r from-emerald-700 to-teal-700 hover:from-emerald-800 hover:to-teal-800',
                                buttonShadow: 'shadow-emerald-700/25',
                                glowOrbColor: 'bg-emerald-400'
                            };
                        case 'info':
                        default:
                            return {
                                categoryLabel: 'Pengumuman Resmi',
                                headerBg: 'bg-gradient-to-br from-sky-500/10 via-blue-500/5 to-white',
                                borderAccent: 'border-sky-200/90 shadow-sky-500/10',
                                badgeStyle: 'bg-sky-100/90 text-sky-950 border-sky-300',
                                badgeDotColor: 'bg-sky-600',
                                iconGradient: 'bg-gradient-to-tr from-sky-500 to-blue-600',
                                iconShadow: 'shadow-[0_8px_20px_-4px_rgba(14,165,233,0.4)]',
                                contentCardBg: 'bg-sky-50/50 border-sky-200/70',
                                buttonGradient: 'bg-gradient-to-r from-sky-600 to-blue-600 hover:from-sky-700 hover:to-blue-700',
                                buttonShadow: 'shadow-sky-600/25',
                                glowOrbColor: 'bg-sky-400'
                            };
                    }
                },

                targetRoleLabel(role) {
                    switch (role) {
                        case 'all': return 'Semua Pengguna';
                        case 'user': return 'Pengguna Biasa (User)';
                        case 'student': return 'Siswa Saja';
                        case 'alumni': return 'Alumni Saja';
                        case 'teacher': return 'Guru Saja';
                        case 'dudi': return 'Mitra DUDI';
                        case 'admin': return 'Khusus Admin';
                        default: return 'Sasaran: ' + (role || 'Semua');
                    }
                },

                openModal(data = null) {
                    if (data) {
                        this.announcement = data;
                    }
                    if (this.announcement && this.announcement.title) {
                        this.isOpen = true;
                    }
                },

                closeModal() {
                    this.isOpen = false;
                },

                confirmAndClose() {
                    if (this.announcement && this.announcement.id) {
                        const storageKey = 'sipintu_announcement_seen_' + this.announcement.id + '_' + (this.announcement.updated_at || '');
                        const sessionKey = 'sipintu_announcement_session_' + this.announcement.id;
                        
                        // Always save session dismiss so page transition inside the same session doesn't re-trigger modal
                        try {
                            sessionStorage.setItem(sessionKey, '1');
                            if (this.dontShowAgain) {
                                localStorage.setItem(storageKey, '1');
                            }
                        } catch (e) {
                            // Storage quota or disabled fallback
                        }
                    }
                    this.closeModal();
                },

                copyContent() {
                    if (!this.announcement) return;
                    const text = (this.announcement.title || '') + '\n\n' + (this.announcement.content || '') + '\n\n— SMKN 1 Bangsri (SiPintu Gateway)';
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(() => {
                            if (window.toast && window.toast.success) {
                                window.toast.success('Isi pengumuman berhasil disalin ke clipboard.', 'Tersalin!');
                            } else {
                                alert('Pengumuman berhasil disalin!');
                            }
                        }).catch(() => {
                            this.fallbackCopy(text);
                        });
                    } else {
                        this.fallbackCopy(text);
                    }
                },

                fallbackCopy(text) {
                    const temp = document.createElement('textarea');
                    temp.value = text;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                    if (window.toast && window.toast.success) {
                        window.toast.success('Isi pengumuman berhasil disalin ke clipboard.', 'Tersalin!');
                    }
                },

                checkAutoShow() {
                    if (!this.announcement || !this.announcement.id) return;
                    
                    const storageKey = 'sipintu_announcement_seen_' + this.announcement.id + '_' + (this.announcement.updated_at || '');
                    const sessionKey = 'sipintu_announcement_session_' + this.announcement.id;
                    
                    let isPermanentlyDismissed = false;
                    let isSessionDismissed = false;

                    try {
                        isPermanentlyDismissed = localStorage.getItem(storageKey) === '1';
                        isSessionDismissed = sessionStorage.getItem(sessionKey) === '1';
                    } catch (e) {
                        // ignore
                    }

                    // For 'danger' (Urgent) announcements, ensure user sees it at least once per session
                    if (this.announcement.type === 'danger' && !isSessionDismissed) {
                        setTimeout(() => { this.isOpen = true; }, 400);
                        return;
                    }

                    // Normal announcements: respect either permanent or session dismissal
                    if (!isPermanentlyDismissed && !isSessionDismissed) {
                        setTimeout(() => { this.isOpen = true; }, 600);
                    }
                },

                init() {
                    this.checkAutoShow();
                }
            };
        }

        window.sipintuAnnouncementModal = sipintuAnnouncementModal;

        // Global helper for opening preview anywhere
        window.openAnnouncementPreview = function(data) {
            window.dispatchEvent(new CustomEvent('open-announcement-popup', { detail: data }));
        };
    })();
</script>
