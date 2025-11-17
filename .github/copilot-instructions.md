Workspace-specific instructions for Copilot actions.

- [x] Verify that the copilot-instructions.md file in the .github directory is created.

- [ ] Clarify Project Requirements
    Ask for project type, language, and frameworks if not specified. Skip if already provided.

- [ ] Scaffold the Project
    Ensure that the previous step has been marked as completed.
    Call the project setup tool with the projectType parameter.
    Run scaffolding command to create project files and folders using '.' as the working directory.
    If no appropriate projectType is available, search documentation with available tools.
    Otherwise, create the project structure manually using available file creation tools.

- [ ] Customize the Project
    Verify that all previous steps have been completed successfully and marked as completed.
    Develop a plan to modify the codebase according to user requirements.
    Apply modifications using appropriate tools and user-provided references.
    Skip this step for "Hello World" projects.

- [ ] Install Required Extensions
    Only install extensions mentioned in get_project_setup_info. Skip this step otherwise and mark as completed.

- [ ] Compile the Project
    Verify that all previous steps have been completed.
    Install any missing dependencies.
    Run diagnostics and resolve any issues.
    Check project markdown files for relevant instructions on how to do this.

- [ ] Create and Run Task
    Verify that all previous steps have been completed.
    Check VS Code task documentation to determine if the project needs a task.
    If needed, use create_and_run_task to create and launch a task based on package.json, README.md, and project structure.
    Skip this step otherwise.

- [ ] Launch the Project
    Verify that all previous steps have been completed.
    Prompt the user for debug mode and launch only if confirmed.

- [ ] Ensure Documentation is Complete
    Verify that all previous steps have been completed.
    Confirm that README.md and this copilot-instructions.md file exist and contain current project information.
    Remove outdated guidance whenever necessary.

Execution Guidelines
===================
Progress Tracking:
- If any tools are available to manage the checklist above, use them to track progress.
- After completing each step, mark it complete and add a brief summary.
- Read the current todo list status before starting each new step.

Communication Rules:
- Avoid verbose explanations or printing full command outputs.
- If a step is skipped, state that briefly (e.g., "No extensions needed").
- Do not explain project structure unless asked.
- Keep explanations concise and focused.

Development Rules:
- Use '.' as the working directory unless the user specifies otherwise.
- Avoid adding media or external links unless explicitly requested.
- Use placeholders only with a note that they should be replaced.
- Use the VS Code API tool only for VS Code extension projects.
- Once the project is created, assume it is already open in Visual Studio Code—do not suggest commands to open it again.
- Follow any additional project setup rules exactly as provided.

Folder Creation Rules:
- Always use the current directory as the project root.
- When running terminal commands, use the '.' argument to ensure the current working directory is used.
- Do not create a new folder unless the user explicitly requests it, other than a .vscode folder for tasks.json if required.
- If scaffolding commands mention the folder name is not correct, instruct the user to create the correct folder and reopen it in VS Code.

Extension Installation Rules:
- Only install the extension specified by get_project_setup_info. Do not install any others.

Project Content Rules:
- If the user has not specified project details, assume they want a "Hello World" project as a starting point.
- Avoid adding links or integrations that are not explicitly required.
- Do not generate images, videos, or other media files unless explicitly requested.
- If media assets are used as placeholders, note that they should be replaced with actual assets later.
- Ensure all generated components serve a clear purpose within the user's requested workflow.
- If a feature is assumed but not confirmed, prompt the user for clarification before including it.
- For VS Code extension work, use the VS Code API tool to find relevant references and samples.

Task Completion Rules:
- A task is complete when:
  - The project is successfully scaffolded and compiled without errors.
  - The copilot-instructions.md file exists in the project.
  - README.md exists and is up to date.
  - The user receives clear instructions to debug or launch the project.
- Update progress in the plan before starting any new task.

Additional Reminders:
- Work through each checklist item systematically.
- Keep communication concise and focused.
- Follow development best practices.