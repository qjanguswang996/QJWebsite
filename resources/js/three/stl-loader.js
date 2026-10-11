import * as THREE from 'three';
import { STLLoader } from 'three/examples/jsm/loaders/STLLoader.js';

export class StlModelLoader {
    constructor() {
        this.loader = new STLLoader();
    }

    /**
     * Load an STL file from URL and return a Promise resolving to THREE.Mesh.
     * @param {string} url
     * @param {function} [onProgress]
     * @returns {Promise<THREE.Mesh>}
     */
    load(url, onProgress) {
        return new Promise((resolve, reject) => {
            this.loader.load(
                url,
                (geometry) => {
                    geometry.computeVertexNormals();

                    const material = new THREE.MeshStandardMaterial({
                        color: 0x3b82f6, // Vibrant indigo-blue
                        roughness: 0.35,
                        metalness: 0.15,
                        side: THREE.DoubleSide,
                    });

                    const mesh = new THREE.Mesh(geometry, material);
                    mesh.castShadow = true;
                    mesh.receiveShadow = true;
                    mesh.name = 'stl_mesh';

                    resolve(mesh);
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
