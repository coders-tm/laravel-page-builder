/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi } from "vitest"
import { FieldRegistry } from "@/core/registry/FieldRegistry"
import React from "react"

const FakeField: React.FC<any> = () => null

describe("FieldRegistry", () => {
  it("register() stores a component by type", () => {
    FieldRegistry.register("test_field", FakeField)
    expect(FieldRegistry.has("test_field")).toBe(true)
  })

  it("get() returns the registered component", () => {
    FieldRegistry.register("test_field_2", FakeField)
    expect(FieldRegistry.get("test_field_2")).toBe(FakeField)
  })

  it("get() returns undefined for unknown type", () => {
    expect(FieldRegistry.get("nonexistent")).toBeUndefined()
  })

  it("has() returns false for unregistered type", () => {
    expect(FieldRegistry.has("definitely_not_registered")).toBe(false)
  })

  it("has() returns true for registered type", () => {
    FieldRegistry.register("has_test", FakeField)
    expect(FieldRegistry.has("has_test")).toBe(true)
  })
})
