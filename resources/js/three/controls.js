import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

export class ViewerControls {
    constructor(camera, domElement) {
        this.camera = camera;
        this.domElement = domElement;
        this.controls = new OrbitControls(camera, domElement);

        this.initDefaults();
    }

    initDefaults() {
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.05;
        this.controls.screenSpacePanning = true;
        this.controls.minDistance = 0.1;
        this.controls.maxDistance = 10000;
        this.controls.autoRotate = false;
        this.controls.autoRotateSpeed = 2.0;

        // Touch settings for mobile support
        this.controls.touches = {
            ONE: THREE.TOUCH.ROTATE,
            TWO: THREE.TOUCH.DOLLY_PAN,
        };

        this.initialCameraPosition = new THREE.Vector3();
        this.initialTargetPosition = new THREE.Vector3();
    }

    setInitialState(cameraPos, targetPos) {
        this.initialCameraPosition.copy(cameraPos);
        this.initialTargetPosition.copy(targetPos);
    }

    reset() {
        this.camera.position.copy(this.initialCameraPosition);
        this.controls.target.copy(this.initialTargetPosition);
        this.controls.update();
    }

    fitToBox(box) {
        if (!box || box.isEmpty()) return;

        const center = new THREE.Vector3();
        box.getCenter(center);

        const size = new THREE.Vector3();
        box.getSize(size);

        const maxDim = Math.max(size.x, size.y, size.z);
        const fov = this.camera.fov * (Math.PI / 180);
        let cameraDistance = Math.abs(maxDim / (2 * Math.tan(fov / 2))) * 1.6;

        if (cameraDistance === 0 || isNaN(cameraDistance)) {
            cameraDistance = 100;
        }

        const direction = new THREE.Vector3(1, 1, 1.2).normalize();
        const newCameraPos = center.clone().add(direction.multiplyScalar(cameraDistance));

        this.camera.position.copy(newCameraPos);
        this.controls.target.copy(center);

        this.camera.near = Math.max(0.01, cameraDistance / 100);
        this.camera.far = Math.max(1000, cameraDistance * 100);
        this.camera.updateProjectionMatrix();

        this.setInitialState(newCameraPos, center);
        this.controls.update();
    }

    setAutoRotate(enabled) {
        this.controls.autoRotate = enabled;
    }

    toggleAutoRotate() {
        this.controls.autoRotate = !this.controls.autoRotate;
        return this.controls.autoRotate;
    }

    update() {
        this.controls.update();
    }

    dispose() {
        this.controls.dispose();
    }
}
