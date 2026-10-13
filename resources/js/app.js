import Alpine from 'alpinejs';
import { ThreeViewer } from './three/index.js';

window.Alpine = Alpine;
window.ThreeViewer = ThreeViewer;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('three-viewer-container');
    if (container) {
        const fileUrl = container.dataset.fileUrl;
        const fileType = container.dataset.fileType;
        const fileName = container.dataset.fileName;

        const statusElement = document.getElementById('viewer-status');
        const statusBadge = document.getElementById('viewer-status-badge');

        const viewer = new ThreeViewer({
            container,
            fileUrl,
            fileType,
            fileName,
            onStatusChange: (statusText, statusType) => {
                if (statusElement) {
                    statusElement.textContent = statusText;
                }
                if (statusBadge) {
                    if (statusType === 'loading') {
                        statusBadge.className = 'px-2 py-0.5 text-[11px] font-semibold rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse';
                    } else if (statusType === 'ready') {
                        statusBadge.className = 'px-2 py-0.5 text-[11px] font-semibold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
                    } else if (statusType === 'error') {
                        statusBadge.className = 'px-2 py-0.5 text-[11px] font-semibold rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30';
                    }
                }
            },
        });

        window.currentThreeViewer = viewer;

        // Interactive control buttons
        const btnReset = document.getElementById('btn-viewer-reset');
        if (btnReset) {
            btnReset.addEventListener('click', () => viewer.resetCamera());
        }

        const btnFit = document.getElementById('btn-viewer-fit');
        if (btnFit) {
            btnFit.addEventListener('click', () => viewer.fitModel());
        }

        const btnWireframe = document.getElementById('btn-viewer-wireframe');
        if (btnWireframe) {
            btnWireframe.addEventListener('click', function () {
                const isActive = viewer.toggleWireframe();
                this.classList.toggle('bg-indigo-600/90', isActive);
                this.classList.toggle('text-white', isActive);
                this.classList.toggle('bg-slate-800/80', !isActive);
                this.classList.toggle('text-slate-300', !isActive);
            });
        }

        const btnAutoRotate = document.getElementById('btn-viewer-autorotate');
        if (btnAutoRotate) {
            btnAutoRotate.addEventListener('click', function () {
                const isActive = viewer.toggleAutoRotate();
                this.classList.toggle('bg-indigo-600/90', isActive);
                this.classList.toggle('text-white', isActive);
                this.classList.toggle('bg-slate-800/80', !isActive);
                this.classList.toggle('text-slate-300', !isActive);
            });
        }

        const btnVisibility = document.getElementById('btn-viewer-visibility');
        if (btnVisibility) {
            btnVisibility.addEventListener('click', function () {
                const isVisible = viewer.toggleModelVisibility();
                this.classList.toggle('bg-slate-800/80', isVisible);
                this.classList.toggle('text-slate-300', isVisible);
                this.classList.toggle('bg-rose-600/80', !isVisible);
                this.classList.toggle('text-rose-200', !isVisible);
            });
        }
    }
});
