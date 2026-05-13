Crear prompts efectivos en Claude (Anthropic) se basa en la claridad, el contexto y la estructura. A diferencia de otros modelos, Claude destaca por seguir instrucciones complejas y manejar grandes contextos, por lo que ser específico y detallado mejora significativamente los resultados. 
Claude Platform
Claude Platform
 +4
Aquí tienes una guía basada en las mejores prácticas de ingeniería de prompts para Claude:
1. Estructura fundamental del Prompt (Método RISE)
Una forma estructurada de crear prompts es usar el método RISE (Rol, Instrucción, Sistema/Contexto, Ejemplos): 
Rol (R): Define quién es Claude (ej. "Actúa como un experto en marketing digital").
Instrucción (I): Qué debe hacer específicamente (ej. "Escribe un correo de ventas").
Contexto (S): Información de fondo (ej. "Para un nuevo software de gestión de tiempo dirigido a freelancers").
Ejemplos (E): Proporcionar ejemplos de lo que quieres (Few-shot prompting) ayuda a Claude a replicar tu estilo. 
Platzi
Platzi
 +4
2. Elementos clave para prompts de alta calidad
Sé claro y directo: Evita la ambigüedad. Sé explícito con lo que buscas en lugar de confiar en que el modelo lo adivine.
Añade contexto detallado: Proporciona datos relevantes como audiencia, objetivos y restricciones (extensión, tono, formato).
Usa etiquetas XML (<tag>): Claude procesa mucho mejor la información si estructuras las instrucciones y los datos entre etiquetas XML.
Ejemplo: Por favor resume el siguiente texto: <texto> [insertar texto aquí] </texto>.
Divide tareas complejas: Para tareas grandes, pide a Claude que piense "paso a paso" (Chain of Thought). Esto mejora la coherencia y reduce errores en problemas complejos. 
Claude Platform
Claude Platform
 +4
3. Técnicas Avanzadas
Encadenamiento de prompts (Prompt Chaining): En lugar de un prompt enorme, usa secuencias de prompts más pequeños. Primero obtén un resumen, luego una estructura y finalmente el texto final.
Meta-prompting (Claude diseña el prompt): Si no sabes cómo pedir algo, pídele a Claude que te ayude a crear el mejor prompt.
Ejemplo: "Quiero crear [tu objetivo]. Ayúdame a diseñar el mejor prompt para Claude para conseguir este resultado".
Usa archivos de contexto (Proyectos): Si usas Claude Pro, utiliza la función de Proyectos para cargar documentos de referencia (archivos, PDFs) que den contexto persistente. 
Reddit
Reddit
 +4
4. Mejores Prácticas y Consejos
Iteración: El primer prompt rara vez es el mejor. Prueba, evalúa y refina tu prompt.
Solicita autoevaluación: Pide a Claude que verifique su propio trabajo antes de darte la respuesta final, lo cual es muy eficaz para reducir alucinaciones.
Define restricciones: Especifica qué no quieres que incluya la respuesta. 
Platzi
Platzi
 +4
Siguiendo estos pasos, aumentarás la precisión y la calidad de las respuestas en Claude, especialmente en tareas complejas de razonamiento, programación o redacción creativa.