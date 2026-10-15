/* Unit 7: Functions
 * Local vs global variables, and scope/lifetime, including a static
 * local variable that retains its value across calls. */
#include <stdio.h>

int globalCounter = 0; /* global variable: visible to all functions */

void incrementCounters(void) {
    int localCounter = 0;          /* local: re-created on each call */
    static int staticCounter = 0;  /* static local: keeps its value */

    localCounter++;
    staticCounter++;
    globalCounter++;

    printf("local = %d, static = %d, global = %d\n",
           localCounter, staticCounter, globalCounter);
}

int main(void) {
    for (int i = 0; i < 3; i++) {
        incrementCounters();
    }
    return 0;
}
