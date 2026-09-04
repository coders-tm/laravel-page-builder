/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import type { AssetProvider, AssetList, Asset } from "@/types/asset"

/**
 * Asset service.
 *
 * Wraps an AssetProvider so UI components never
 * need to know which provider is being used.
 *
 * Usage:
 *   const service = new AssetService(laravelAssetProvider);
 *   const assets = await service.list({ page: 1 });
 */
export class AssetService {
  private provider: AssetProvider

  constructor(provider: AssetProvider) {
    this.provider = provider
  }

  list(params: { page?: number; search?: string } = {}): Promise<AssetList> {
    return this.provider.list(params)
  }

  upload(file: File): Promise<Asset> {
    return this.provider.upload(file)
  }
}
