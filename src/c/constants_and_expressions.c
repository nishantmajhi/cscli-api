/* Unit 2: Elements of C
 * Constants/literals, variables, expressions, and comments. */
#include <stdio.h>

#define PI 3.14159 /* symbolic constant */

int main(void) {
    const int MAX = 100;       /* qualified constant */
    int radius = 5;
    float area;

    area = PI * radius * radius; /* expression */

    printf("MAX constant   = %d\n", MAX);
    printf("Area of circle = %.2f\n", area);

    return 0;
}
