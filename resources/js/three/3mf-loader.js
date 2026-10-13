import * as THREE from 'three';
import { ThreeMFLoader } from 'three/examples/jsm/loaders/3MFLoader.js';

export class ThreeMfModelLoader {
    constructor() {
        this.loader = new ThreeMFLoader();
    }

    /**
     * Load a 3MF file from URL and return a Promise resolving to THREE.Object3D/Group.
     * @param {string} url
     * @param {function} [onProgress]
     * @returns {Promise<THREE.Object3D>}
     */
    load(url, onProgress) {
        return new Promise((resolve, reject) => {
            this.loader.load(
                url,
                (object) => {
                    object.traverse((child) => {
                        if (child.isMesh) {
                            child.castShadow = true;
                            child.receiveShadow = true;

                            if (child.geometry) {
                                child.geometry.computeVertexNormals();
                            }

                            if (!child.material) {
                                child.material = new THREE.MeshStandardMaterial({
                                    color: 0x6366f1, // Indigo
                                    roughness: 0.35,
                                    metalness: 0.15,
                                    side: THREE.DoubleSide,
                                });
                            } else if (Array.isArray(child.material)) {
                                child.material.forEach((mat) => {
                                    mat.side = THREE.DoubleSide;
                                });
                            } else {
                                child.material.side = THREE.DoubleSide;
                            }
                        }
                    });

                    object.name = '3mf_group';
                    resolve(object);
                },
                (xhr) => {
                    if (onProgress && xhr.total > 0) {
                        onProgress((xhr.loaded / xhr.total) * 100);
                    }
                },
                (error) => {
                    reject(error);
                }
            );
        });
    }
}
