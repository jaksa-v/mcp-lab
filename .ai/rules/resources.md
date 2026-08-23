---
paths:
  - 'app/Mcp/{Resources/QueueApp.php,Tools/ShowQueueTool.php,Tools/GetQueueDataTool.php}'
---

# Resources

## QueueApp is the iframe, not desk://queue
QueueApp is an AppResource at ui://. show_queue is model-visible with #[RendersApp]. get_queue_data is Visibility::App only so the iframe can refresh without handing the model a second list tool. Blade + Alpine inside <x-mcp::app>, not Inertia. Cursor lists the tools but does not render MCP Apps; Pest is how you know it works.
