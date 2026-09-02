/**
 * Thin wrapper around the native Fetch API that handles:
 *
 *  - CSRF token injection (reads from <meta name="csrf-token">)
 *  - Accept / Content-Type headers for JSON payloads
 *  - Consistent error handling with typed ApiError
 *
 * All methods return the parsed JSON body directly.
 */

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly body?: unknown,
  ) {
    super(message)
    this.name = "ApiError"
  }
}

function getCsrfToken(): string | null {
  const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
  return meta?.content ?? null
}

function buildHeaders(extra: HeadersInit = {}): Record<string, string> {
  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(extra as Record<string, string>),
  }

  const token = getCsrfToken()
  if (token) {
    headers["X-CSRF-TOKEN"] = token
  }

  return headers
}

async function handleResponse<T = unknown>(response: Response): Promise<T> {
  if (response.ok) {
    // Handle 204 No Content
    if (response.status === 204) {
      return undefined as T
    }
    return response.json() as Promise<T>
  }

  let body: unknown
  try {
    body = await response.json()
  } catch {
    body = await response.text().catch(() => null)
  }

  const message =
    typeof body === "object" && body !== null && "message" in body
      ? ((body as Record<string, unknown>).message as string)
      : `HTTP ${response.status} ${response.statusText}`

  throw new ApiError(message, response.status, body)
}

/**
 * Perform a GET request that returns parsed JSON.
 */
export async function get<T = unknown>(url: string): Promise<T> {
  const response = await fetch(url, {
    method: "GET",
    headers: buildHeaders(),
    credentials: "same-origin",
  })

  return handleResponse<T>(response)
}

/**
 * Perform a POST request with a JSON body.
 */
export async function post<T = unknown>(url: string, data?: unknown): Promise<T> {
  const response = await fetch(url, {
    method: "POST",
    headers: buildHeaders({ "Content-Type": "application/json" }),
    credentials: "same-origin",
    body: data !== undefined ? JSON.stringify(data) : undefined,
  })

  return handleResponse<T>(response)
}

/**
 * Perform a PUT request with a JSON body.
 */
export async function put<T = unknown>(url: string, data?: unknown): Promise<T> {
  const response = await fetch(url, {
    method: "PUT",
    headers: buildHeaders({ "Content-Type": "application/json" }),
    credentials: "same-origin",
    body: data !== undefined ? JSON.stringify(data) : undefined,
  })

  return handleResponse<T>(response)
}

/**
 * Perform a DELETE request.
 */
export async function del<T = unknown>(url: string): Promise<T> {
  const response = await fetch(url, {
    method: "DELETE",
    headers: buildHeaders(),
    credentials: "same-origin",
  })

  return handleResponse<T>(response)
}

export default { get, post, put, del }
