# DRM-X 5.0 Moodle Plugin - Video DRM and LMS Course Protection

[![DRM-X 5.0](https://img.shields.io/badge/DRM--X-5.0-155EEF)](https://www.drm-x.com/en/products/drm-x-5.0/features)
[![Plugin version](https://img.shields.io/badge/plugin-v1.6.1-2E8B57)](./version.php)
[![Moodle](https://img.shields.io/badge/Moodle-3.8%2B-F98012)](https://moodle.org/)
[![PHP](https://img.shields.io/badge/PHP-SOAP_required-777BB4)](#requirements)

**DRM-X 5.0 Protected Video Integration** is a Moodle text-filter plugin for protecting paid video courses and other LMS content with DRM-X digital rights management. It connects Moodle login and active course enrollment with DRM-X 5.0 license delivery, allowing authorized students to play encrypted videos through the ZJGet secure browser.

The plugin is designed for online education, membership websites, corporate training, and commercial e-learning platforms that need Moodle video encryption, course access control, dynamic watermarking, license revocation, and stronger protection against unauthorized sharing or screen recording.

> 中文说明请参阅[中文简介](#中文简介)。

## Why use DRM-X with Moodle?

Ordinary Moodle access restrictions protect the course page, but a copied media URL or downloaded file may still be redistributed. DRM-X 5.0 encrypts the content and requires a valid license before playback. This plugin links that license decision to the learner's Moodle account and course enrollment.

Typical use cases include:

- Moodle video DRM and encrypted online course delivery
- Paid LMS course protection and student authorization
- Corporate training video encryption
- License-based access to downloadable protected videos
- User-specific watermarking and anti-piracy controls
- Blocking revoked or locked DRM-X users

Learn more about [DRM-X 5.0 digital content protection](https://www.drm-x.com/en/products/drm-x-5.0/features) and the [DRM-X 5.0 security architecture](https://www.drm-x.com/en/products/drm-x-5.0/features/new-drm-security-architecture).

## Features

- Requires the user to sign in to Moodle before a DRM license can be issued.
- Checks active enrollment in a Moodle course assigned to the DRM-X License Profile.
- Supports one or multiple Moodle course IDs, such as `123-124-208`.
- Automatically creates the Moodle user in DRM-X when the DRM-X account does not exist.
- Checks `CheckUserIsRevoked` and `GetUserDetails`; revoked or locked users cannot obtain a license.
- Supports both DRM-X 5.0 International (`5.drm-x.com`) and China (`5.drm-x.cn`) services.
- Supports a fixed RightsID with automatic permission updates.
- Supports reading the RightsID selected by the DRM-X License Profile from ZJGet POST data.
- Embeds protected video with the `[zjget-player]` Moodle filter tag.
- Avoids loading the ZJGet player overlay while a teacher is editing the course.
- Preserves the pending license request across the normal Moodle login flow.
- Includes English and Simplified Chinese interface strings.

## License request workflow

1. A learner opens a DRM-X 5.0 encrypted video in ZJGet.
2. DRM-X sends the license request to Moodle's `licstore5.php` endpoint.
3. Moodle requires the learner to sign in if there is no active session.
4. The plugin reads `YourProductID` and checks active enrollment in the corresponding Moodle course.
5. The plugin creates the DRM-X user when necessary.
6. Existing DRM-X users are checked for revocation and lockout.
7. Depending on the configured RightsID mode, the plugin either updates the fixed permission or uses the RightsID posted by ZJGet.
8. DRM-X issues the license and ZJGet opens the protected content.

## Requirements

- Moodle 3.8 or later
- PHP SOAP extension enabled
- HTTPS enabled on the Moodle website
- A DRM-X 5.0 account with website integration access
- A DRM-X 5.0 License Profile and user group
- ZJGet secure browser for opening DRM-X 5.0 protected content

The Moodle login URL and the DRM-X integration URL should use the same HTTPS domain. A different domain, HTTP/HTTPS mismatch, or a completely separate browser cookie store can cause the learner to be asked to sign in again.

## Download

- [Download the installable Moodle plugin ZIP (v1.6.1)](./dist/drmx5-moodle-1.6.1.zip)
- [Read the complete DRM-X 5.0 Moodle Plugin User Guide (PDF)](./docs/DRM-X-5.0-Moodle-Integration-Plugin-User-Guide-v1.6.1.pdf)

## Installation

### Install from ZIP

1. Sign in to Moodle as a site administrator.
2. Open **Site administration > Plugins > Install plugins**.
3. Upload `drmx5-moodle-1.6.1.zip`.
4. Follow Moodle's prompts to validate the plugin and upgrade the database.
5. Open **Site administration > Plugins > Filters > Manage filters**.
6. Set **DRM-X 5.0 Protected Video Integration** to **On**.
7. Open **Site administration > Development > Purge caches** and purge all caches.

### Install with Git

From the Moodle installation directory:

```bash
git clone https://github.com/Haihaisoft/drm-x5-moodle-plugin.git filter/drmx5
```

Then open **Site administration > Notifications** to complete the Moodle plugin installation. The final plugin path must be:

```text
filter/drmx5/version.php
```

## Plugin settings

Open **Site administration > Plugins > Filters > DRM-X 5.0 Protected Video Integration**.

| Setting | Description |
| --- | --- |
| `AdminEmail` | Administrator email address of the DRM-X 5.0 account. |
| `WebServiceAuthStr` | Web Service Authorization String configured in DRM-X 5.0. Treat it as a secret. |
| `GroupID` | DRM-X 5.0 user group ID used when creating users and issuing licenses. |
| Service area | Select **International** for `5.drm-x.com` or **China** for `5.drm-x.cn`. |
| RightsID mode | Use the RightsID supplied by the License Profile through ZJGet POST data, or use a fixed RightsID and update it automatically. |
| Fixed RightsID | Required only when the fixed RightsID mode is selected. |

### RightsID modes

**Read RightsID from ZJGet POST data**

ZJGet reads the permission configured in the DRM-X 5.0 License Profile and posts the corresponding RightsID. The plugin uses that RightsID to obtain the license and does not call `UpdateRightWithDisableVirtualMachine`.

**Fixed RightsID and automatically update permission**

The plugin uses the configured Fixed RightsID and calls `UpdateRightWithDisableVirtualMachine` before requesting the license. The matched Moodle course supplies the permission start and expiration dates:

- `BeginDate`: Moodle course start date
- `ExpirationDate`: Moodle course end date
- No course end date: ten years after the license request date
- `ExpirationAfterFirstUse`: `-1`

## DRM-X 5.0 configuration

In **DRM-X 5.0 Account Settings > Website Integration Settings**, configure the Web Service Authorization String and use this custom login/integration URL:

```text
https://your-moodle-domain.example/filter/drmx5/licstore5.php
```

The page name must remain `licstore5.php`. Use the exact HTTPS hostname that learners use to sign in to Moodle.

### Assign a Moodle course to the License Profile

Open the Moodle course and read its numeric ID from the URL:

```text
https://your-moodle-domain.example/course/view.php?id=123
```

Enter `123` in the DRM-X License Profile **Your Product ID** field. Use the Moodle `course.id`, not the course context ID.

For multiple courses, separate IDs with a standard hyphen:

```text
123-124-208
```

The learner is authorized when actively enrolled in at least one listed course. If more than one course matches, the first accessible course in the list supplies the dates used by fixed RightsID mode. Enrollment in course `123` does not grant access to course `124`; it only authorizes content whose License Profile lists a course in which that learner is enrolled.

### Moodle groups

The plugin validates course enrollment. Moodle group membership does not grant course access by itself, so enrollment workflows that also assign a group remain compatible. The plugin does not enforce activity-level **Restrict access by group** rules. Use separate courses or extend the authorization logic when different groups inside one course must receive different protected videos.

## Embed an encrypted video

Add a **Text and media area** or **Page** to the Moodle course. Place the complete protected video URL between the filter tags:

```text
[zjget-player]https://cdn.example.com/course/protected-video.mp4[/zjget-player]
```

Important notes:

- The video URL must begin with `http://` or `https://`.
- Do not use Markdown link syntax inside the tag.
- HTML/source mode is the most reliable when the editor automatically changes URLs.
- Use one ZJGet player tag per page because the integration uses a fixed element ID.
- The plugin loads the official player scripts from `www.zjget.com`; the scripts do not need to be copied into the plugin.
- While course editing is enabled, the plugin displays a placeholder instead of the player so the ZJGet browser-detection overlay does not cover Moodle dialogs.

## Verification checklist

| Test | Expected result |
| --- | --- |
| User is not signed in | Moodle redirects the user to its normal login page. |
| User is signed in and actively enrolled | The user obtains a license and opens the protected video. |
| User is not enrolled in an assigned course | The license request is denied. |
| DRM-X user does not exist | The plugin creates the DRM-X user automatically. |
| DRM-X user is revoked or locked | The license request is denied. |
| License Profile contains multiple course IDs | Enrollment in any one listed course passes the course check. |

## Troubleshooting

| Problem | Suggested check |
| --- | --- |
| The license page asks the learner to sign in again | Confirm that Moodle login and `licstore5.php` use the same HTTPS domain. |
| Browser reports an insecure form submission | Enable HTTPS for Moodle and configure the integration URL with `https://`. |
| User is reported as not enrolled | Confirm that `YourProductID` contains the Moodle `course.id` and that enrollment is active and not suspended. |
| The filter tag is displayed as text | Enable the DRM-X filter and purge Moodle caches. |
| Player reports an invalid URL | Put one complete HTTP or HTTPS URL inside the filter tag. |
| Moodle cannot contact DRM-X | Enable PHP SOAP and verify the International/China service selection. |

## Privacy and security

When creating a DRM-X user or requesting a license, the integration may send the Moodle username, email address, full name, IP address, and DRM client/device request data to the selected DRM-X 5.0 service. Review your organization's privacy notice and applicable data-protection requirements before deployment.

Always use HTTPS and protect `WebServiceAuthStr` as a secret. Do not publish real account credentials in issues, screenshots, configuration examples, or source control.

## 中文简介

**DRM-X 5.0 Moodle 加密视频集成插件**是一款 Moodle 文本过滤器插件，用于将 Moodle 登录状态、课程报名权限与 DRM-X 5.0 数字版权管理许可证结合起来。它适用于在线教育、付费课程、企业培训和会员视频，可帮助实现 Moodle 视频加密、LMS 课程内容保护、防止未授权传播、许可证吊销和用户水印等功能。

主要功能：

- 用户必须登录 Moodle，并且有效加入 DRM-X 许可证模板指定的课程。
- 支持单个或多个 Moodle 课程 ID，例如 `123-124-208`。
- DRM-X 用户不存在时自动创建。
- 已吊销或锁定的 DRM-X 用户不能获取许可证。
- 支持 DRM-X 5.0 中国版与国际版接口。
- 支持从 License Profile 读取 RightsID，或使用固定 RightsID 自动更新权限。
- 使用 `[zjget-player]` 标签在 Moodle 课程中嵌入加密视频。
- 课程编辑状态下不加载播放器，避免播放器遮挡 Moodle 编辑窗口。

快速部署：

1. 下载并安装 [`drmx5-moodle-1.6.1.zip`](./dist/drmx5-moodle-1.6.1.zip)。
2. 在 Moodle 的**管理过滤器**页面启用 **DRM-X 5.0 加密视频集成**。
3. 填写 `AdminEmail`、`WebServiceAuthStr`、`GroupID`、服务区域和 RightsID 模式。
4. 在 DRM-X 5.0 网站集成设置中填写：

   ```text
   https://你的Moodle域名/filter/drmx5/licstore5.php
   ```

5. 将 Moodle 课程网址中的 `course.id` 填入 License Profile 的 **Your Product ID**。多个课程 ID 使用半角连字符分隔。
6. 在 Moodle 的“文本和媒体区”或“网页”中添加：

   ```text
   [zjget-player]https://你的加密视频完整地址.mp4[/zjget-player]
   ```

7. 使用普通学生账号测试已报名、未报名、吊销和锁定等情况。

完整操作方法请阅读[英文 PDF 使用指南](./docs/DRM-X-5.0-Moodle-Integration-Plugin-User-Guide-v1.6.1.pdf)。

## Support and product information

- [DRM-X 5.0 product overview](https://www.drm-x.com/en)
- [DRM-X 5.0 features](https://www.drm-x.com/en/products/drm-x-5.0/features)
- [DRM-X 5.0 encryption quick guide](https://www.drm-x.com/en/products/drm-x-5.0/demo-tutorial/Tutorial/zjget_windows_encryption)
- [Haihaisoft](https://www.haihaisoft.com/)

For integration questions or product support, please use Haihaisoft's official contact channels. For reproducible plugin defects, open a GitHub issue without including DRM-X credentials or personal data.

## Maintainer

Developed and maintained by [Haihaisoft](https://www.haihaisoft.com/).
