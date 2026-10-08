# 開發規範與統一架構 (Development Guidelines)

本文件定義 Laravel 後台管理系統與 3D 解析模組的統一開發規範。所有開發人員與模組開發皆需嚴格遵循以下規範。

---

## 1. 技術棧與版本規範

| 項目 | 技術 / 版本 | 說明 |
| --- | --- | --- |
| **Backend Framework** | Laravel 13.x | 使用專案目前版本，嚴禁任意跨大版本升級 |
| **PHP Version** | PHP 8.3+ | 遵循 PHP 8.3+ 語法特性與型別提示 |
| **Database** | MySQL / MariaDB | 預設資料庫驅動，Migration 需考慮相容性 |
| **Frontend Templates** | Blade | 搭配 Component 化規範 |
| **CSS Framework** | Tailwind CSS v4 | 簡潔現代 UI 設計 |
| **JavaScript / Bundler** | Vite + ES Modules | 原生 ESM 模組化載入 |
| **Icon Library** | Lucide Icons | 統一圖標風格 |
| **3D Rendering Engine**| Three.js | 原生 WebGL 渲染引擎 |
| **3D Navigation** | OrbitControls | 3D 視角控制 |
| **3D Formats** | STL, 3MF | 支援 STL 與 3MF 格式解析與預覽 |
| **Storage System** | Laravel Storage | 本地/雲端檔案儲存代理，不寫死絕對路徑 |
| **Authentication** | Laravel 原生認證 | 使用 Auth / Gate / Policy 原生機制 |
| **UI Design Style** | Modern SaaS Dashboard | 簡潔現代化，支援 Desktop / Tablet / Mobile (RWD) |
| **System Language** | 繁體中文 (zh_TW) | 後台介面與提示訊息統一使用繁體中文 |
| **Code Style** | PSR-12 + Laravel Best Practices | 使用 Pint 進行自動格式化 |
| **Testing** | PHPUnit / Laravel Feature Test | 所有功能與 API 均需包含 Feature / Unit 測試 |

---

## 2. 核心原則與安全規範

1. **技術棧固定**：切勿自行更換或引入未經討論的核心套件或框架（例如無故導入 Vue/React 等前端重型框架，除非專案要求）。
2. **精簡套件**：優先採用 Laravel 原生功能（Auth, Session, Storage, Queue, Validation, Policy），避免導入不必要的大型套件。
3. **3D 隱私與安全性**：
   - **嚴禁將 3D 檔案傳送到任何第三方服務或外部 API**。所有 3D 模型解析與渲染必須於前端瀏覽器（Three.js）或後端內部完成。
   - **嚴禁使用 iframe 顯示 3D 內容**，3D Viewer 必須由本地 Three.js 直接建立與控制。
4. **RWD 響應式**：所有頁面與 3D 檢視器需完美支援 Desktop、Tablet 及 Mobile 螢幕尺寸。
5. **完整實作**：禁止僅製作前端 UI Mockup，所有視圖與 API 必須具備完整的後端邏輯、資料庫互動與測試驗證。

---

## 3. 目錄結構與程式碼規範

- **Controllers**：保持 Controller 輕量，複雜業務邏輯抽離至 Services 或 Actions。
- **Models**：啟用 strict types / fillable 宣告，善用 Eloquent Relationships 與 Scope。
- **Views**：Blade components 放在 `resources/views/components/`，頁面佈局統一使用 `layouts.app`。
- **JavaScript Module**：3D 解析邏輯模組化存放於 `resources/js/three/`。
