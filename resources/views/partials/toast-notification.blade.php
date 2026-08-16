<div x-data="{ 
        toasts: [],
        addToast(message, isSuccess = true) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, isSuccess });
            setTimeout(() => {
                this.removeToast(id);
            }, 4000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-on:show-toast.window="addToast($event.detail.message, $event.detail.isSuccess ?? true)"
    x-on:favorite-updated.window="if ($event.detail.message) addToast($event.detail.message, $event.detail.is_favorited)"
    class="fixed top-5 right-5 z-50 flex flex-col space-y-3 max-w-sm w-full pointer-events-none px-4 sm:px-0">
    
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4"
             x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="pointer-events-auto flex items-center justify-between p-4 rounded-2xl shadow-xl border backdrop-blur-md transition-all duration-300"
             :class="toast.isSuccess 
                ? 'bg-white/95 text-slate-900 border-emerald-200 shadow-emerald-950/10' 
                : 'bg-white/95 text-slate-900 border-slate-200 shadow-slate-950/10'">
            
            <div class="flex items-center space-x-3 min-w-0">
                <div class="p-2 rounded-xl shrink-0"
                     :class="toast.isSuccess ? 'bg-amber-100 text-amber-600 border border-amber-200' : 'bg-slate-100 text-slate-500 border border-slate-200'">
                    <template x-if="toast.isSuccess">
                        <svg class="w-5 h-5 fill-amber-400" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </template>
                    <template x-if="!toast.isSuccess">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                </div>
                <div class="text-xs font-bold leading-snug break-words text-slate-800" x-text="toast.message"></div>
            </div>

            <button @click="removeToast(toast.id)" type="button" class="ml-3 p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </template>
</div>
