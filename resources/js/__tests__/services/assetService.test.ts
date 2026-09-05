/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi } from "vitest"
import { AssetService } from "@/services/assetService"

const mockProvider = {
  list: vi.fn().mockResolvedValue({ data: [], total: 0, per_page: 20 }),
  upload: vi.fn().mockResolvedValue({ id: "1", url: "/img.jpg", name: "img.jpg" }),
}

describe("AssetService", () => {
  it("list() delegates to provider with params", async () => {
    const service = new AssetService(mockProvider as any)
    await service.list({ page: 2, search: "photo" })
    expect(mockProvider.list).toHaveBeenCalledWith({ page: 2, search: "photo" })
  })

  it("list() works with no params", async () => {
    const service = new AssetService(mockProvider as any)
    await service.list()
    expect(mockProvider.list).toHaveBeenCalledWith({})
  })

  it("upload() delegates to provider with the file", async () => {
    const service = new AssetService(mockProvider as any)
    const file = new File(["content"], "photo.jpg", { type: "image/jpeg" })
    await service.upload(file)
    expect(mockProvider.upload).toHaveBeenCalledWith(file)
  })

  it("upload() returns the asset from the provider", async () => {
    const service = new AssetService(mockProvider as any)
    const file = new File([""], "x.jpg")
    const result = await service.upload(file)
    expect(result).toEqual({ id: "1", url: "/img.jpg", name: "img.jpg" })
  })
})
