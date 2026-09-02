import React, { useEffect, useState } from "react"

interface LoadingBarProps {
  isProcessing: boolean
}

export const LoadingBar: React.FC<LoadingBarProps> = ({ isProcessing }) => {
  const [visible, setVisible] = useState(false)
  const [progress, setProgress] = useState(0)

  useEffect(() => {
    let interval: NodeJS.Timeout

    if (isProcessing) {
      setVisible(true)
      setProgress(10)
      interval = setInterval(() => {
        setProgress((prev) => {
          if (prev >= 90) return prev
          return prev + Math.random() * 5
        })
      }, 200)
    } else {
      setProgress(100)
      const timeout = setTimeout(() => {
        setVisible(false)
        setProgress(0)
      }, 300)
      return () => {
        clearInterval(interval)
        clearTimeout(timeout)
      }
    }

    return () => clearInterval(interval)
  }, [isProcessing])

  if (!visible) return null

  return (
    <div className="pointer-events-none absolute top-0 right-0 left-0 z-[100] h-1 bg-transparent">
      <div
        className="h-full bg-blue-600 transition-all duration-300 ease-out"
        style={{ width: `${progress}%` }}
      />
    </div>
  )
}
