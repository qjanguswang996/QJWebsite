import * as THREE from 'three';
import { StlModelLoader } from './stl-loader.js';
import { ThreeMfModelLoader } from './3mf-loader.js';
import { ViewerControls } from './controls.js';

export class ThreeViewer {
    constructor(options = {}) {
        this.container = options.container;
        this.fileUrl = options.fileUrl;
        this.fileType = options.fileType ? options.fileType.toLowerCase() : '';
        this.fileName = options.fileName || '3D Model';

        this.onStatusChange = options.onStatusChange || (() => {});
        this.onProgress = options.onProgress || (() => {});

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.currentModel = null;
        this.gridHelper = null;
        this.axesHelper = null;
        this.animationFrameId = null;
        this.resizeObserver = null;

        this.isWireframe = false;
        this.isModelVisible = true;
        this.isAutoRotate = false;

        this.stlLoader = new StlModelLoader();
        this.threeMfLoader = new ThreeMfModelLoader();

        if (this.container) {
            this.init();
        }
    }

    init() {
        const width = this.container.clientWidth || 800;
        const height = this.container.clientHeight || 500;

        // Scene setup
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0f172a); // Slate-900 dark background

        // Camera setup
        this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 10000);
        this.camera.position.set(100, 100, 100);

        // WebGL Renderer setup
        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            preserveDrawingBuffer: true,
        });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        // Append canvas element
        this.container.appendChild(this.renderer.domElement);

        // OrbitControls wrapper
        this.controls = new ViewerControls(this.camera, this.renderer.domElement);

        // Lights setup
        this.setupLights();

        // Helpers setup (Grid & RGB Axes)
        this.setupHelpers();

        // Responsive Resize Observer
        this.setupResizeObserver();

        // Start render loop
        this.animate();

        // Load model if fileUrl and fileType provided
        if (this.fileUrl && this.fileType) {
            this.loadModel(this.fileUrl, this.fileType);
        }
    }

    setupLights() {
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
        this.scene.add(ambientLight);

        const keyLight = new THREE.DirectionalLight(0xffffff, 1.2);
        keyLight.position.set(100, 200, 150);
        keyLight.castShadow = true;
        keyLight.shadow.mapSize.width = 2048;
        keyLight.shadow.mapSize.height = 2048;
        this.scene.add(keyLight);

        const fillLight = new THREE.DirectionalLight(0x90b0ff, 0.6);
        fillLight.position.set(-100, -50, -100);
        this.scene.add(fillLight);

        const backLight = new THREE.DirectionalLight(0xffffff, 0.4);
        backLight.position.set(0, -100, 100);
        this.scene.add(backLight);
    }

    setupHelpers() {
        // Grid Helper
        this.gridHelper = new THREE.GridHelper(200, 40, 0x475569, 0x334155);
        this.gridHelper.position.y = -0.01;
        this.scene.add(this.gridHelper);

        // Axes Helper
        this.axesHelper = new THREE.AxesHelper(30);
        this.scene.add(this.axesHelper);
    }

    async loadModel(url, extension) {
        const ext = (extension || '').toLowerCase().trim();
        this.onStatusChange('載入模型中...', 'loading');

        try {
            if (this.currentModel) {
                this.scene.remove(this.currentModel);
            }

            let loadedObject;
            if (ext === 'stl') {
                loadedObject = await this.stlLoader.load(url, (percent) => {
                    this.onProgress(percent);
                    this.onStatusChange(`載入中 (${Math.round(percent)}%)...`, 'loading');
                });
            } else if (ext === '3mf') {
                loadedObject = await this.threeMfLoader.load(url, (percent) => {
                    this.onProgress(percent);
                    this.onStatusChange(`載入中 (${Math.round(percent)}%)...`, 'loading');
                });
            } else {
                throw new Error(`不支援的檔案格式: ${ext}`);
            }

            this.currentModel = loadedObject;

            // Compute bounding box and center geometry at origin
            const box = new THREE.Box3().setFromObject(loadedObject);
            const center = new THREE.Vector3();
            box.getCenter(center);

            // Shift object so center is at (0, 0, 0)
            loadedObject.position.sub(center);

            // Recompute box after center alignment
            const centeredBox = new THREE.Box3().setFromObject(loadedObject);

            // Align grid to bottom of model
            this.gridHelper.position.y = centeredBox.min.y;

            this.scene.add(loadedObject);

            // Fit model in camera view
            this.controls.fitToBox(centeredBox);

            this.onStatusChange('模型已載入', 'ready');
        } catch (error) {
            console.error('Model load error:', error);
            this.onStatusChange(`載入失敗: ${error.message || '無法解析模型'}`, 'error');
        }
    }

    resetCamera() {
        if (this.controls) {
            this.controls.reset();
        }
    }

    fitModel() {
        if (this.currentModel && this.controls) {
            const box = new THREE.Box3().setFromObject(this.currentModel);
            this.controls.fitToBox(box);
        }
    }

    toggleWireframe() {
        this.isWireframe = !this.isWireframe;
        if (this.currentModel) {
            this.currentModel.traverse((child) => {
                if (child.isMesh && child.material) {
                    if (Array.isArray(child.material)) {
                        child.material.forEach((mat) => {
                            mat.wireframe = this.isWireframe;
                        });
                    } else {
                        child.material.wireframe = this.isWireframe;
                    }
                }
            });
        }
        return this.isWireframe;
    }

    toggleModelVisibility() {
        this.isModelVisible = !this.isModelVisible;
        if (this.currentModel) {
            this.currentModel.visible = this.isModelVisible;
        }
        return this.isModelVisible;
    }

    toggleAutoRotate() {
        if (this.controls) {
            this.isAutoRotate = this.controls.toggleAutoRotate();
        }
        return this.isAutoRotate;
    }

    toggleGrid() {
        if (this.gridHelper) {
            this.gridHelper.visible = !this.gridHelper.visible;
            return this.gridHelper.visible;
        }
        return false;
    }

    toggleAxes() {
        if (this.axesHelper) {
            this.axesHelper.visible = !this.axesHelper.visible;
            return this.axesHelper.visible;
        }
        return false;
    }

    setupResizeObserver() {
        this.resizeObserver = new ResizeObserver(() => {
            this.resize();
        });
        this.resizeObserver.observe(this.container);
    }

    resize() {
        if (!this.container || !this.renderer || !this.camera) return;

        const width = this.container.clientWidth;
        const height = this.container.clientHeight;

        if (width === 0 || height === 0) return;

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();

        this.renderer.setSize(width, height);
    }

    animate() {
        this.animationFrameId = requestAnimationFrame(() => this.animate());

        if (this.controls) {
            this.controls.update();
        }

        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }

    destroy() {
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }

        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }

        if (this.controls) {
            this.controls.dispose();
        }

        if (this.renderer) {
            this.renderer.dispose();
            if (this.renderer.domElement && this.renderer.domElement.parentNode) {
                this.renderer.domElement.parentNode.removeChild(this.renderer.domElement);
            }
        }
    }
}
